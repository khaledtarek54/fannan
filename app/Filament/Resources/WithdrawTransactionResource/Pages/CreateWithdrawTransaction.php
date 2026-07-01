<?php

namespace App\Filament\Resources\WithdrawTransactionResource\Pages;

use App\Enums\TransactionType;
use App\Filament\Resources\WithdrawTransactionResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
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
        $user = User::find($data['user_id']);
        $netAmount = $user->total_income - $user->total_withdraw;

        if ($netAmount < $data['amount']) {
            Notification::make()
                ->title('Insufficient Funds')
                ->body('The withdrawal amount exceeds the available balance.')
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'amount' => 'The withdrawal amount exceeds the available balance.',
            ]);

        }

        $data['type'] = TransactionType::WITHDRAW->value;
        $data['is_completed'] = true;
        return $data;
    }

    public function handleRecordCreation(array $data): Model
    {
        return static::getModel()::create($data);
    }

}
