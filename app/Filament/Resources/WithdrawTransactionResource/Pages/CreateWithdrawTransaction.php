<?php

namespace App\Filament\Resources\WithdrawTransactionResource\Pages;

use App\Enums\TransactionType;
use App\Filament\Resources\WithdrawTransactionResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateWithdrawTransaction extends CreateRecord
{
    protected static string $resource = WithdrawTransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = TransactionType::WITHDRAW->value;
        $data['is_completed'] = true;
        return $data;
    }

    /**
     * [DASH-P1] Do the balance check and the insert atomically, under a row lock on the artist.
     * The old flow read the balance in mutateFormDataBeforeCreate and inserted separately, so two
     * payouts issued in the same window could BOTH pass the check and over-pay (a read-then-write
     * TOCTOU race). Locking the user row serializes concurrent payouts for that user, and the sums
     * are recomputed from the database (not the in-memory accessors) inside the same transaction.
     */
    public function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->whereKey($data['user_id'])->lockForUpdate()->firstOrFail();

            $income = $user->transactions()->where('type', TransactionType::INCOME->value)->sum('amount');
            $withdrawn = $user->transactions()->where('type', TransactionType::WITHDRAW->value)->sum('amount');
            $available = $income - $withdrawn;

            if ($available < $data['amount']) {
                Notification::make()
                    ->title('Insufficient Funds')
                    ->body('The withdrawal amount exceeds the available balance.')
                    ->danger()
                    ->send();
                throw ValidationException::withMessages([
                    'amount' => 'The withdrawal amount exceeds the available balance.',
                ]);
            }

            return static::getModel()::create($data);
        });
    }
}
