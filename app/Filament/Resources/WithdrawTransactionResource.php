<?php

namespace App\Filament\Resources;

use App\Enums\TransactionType;
use App\Filament\Resources\WithdrawTransactionResource\Pages;
use App\Models\Transaction;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WithdrawTransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.transactions');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.withdraw_talents');
    }

    public static function getModelLabel(): string
    {
        return __('app.withdraw_talent');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.withdraw_talents');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->options(User::query()->artist()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->label(trans('app.artist'))
                            ->required() // without it, CreateWithdrawTransaction does User::find(null) -> null -> 500
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                $user = User::find($state);
                                $set('available_amount', $user ? ($user->total_income - $user->total_withdraw) : null);
                            }),
                        TextInput::make('available_amount')
                            ->label(trans('app.available_amount'))
                            ->disabled()
                            ->dehydrated(false) // display-only helper; never persist it (not a column)
                            ->suffix(currency_code()),
                        TextInput::make('amount')
                            ->label(trans('app.amount'))
                            ->numeric()
                            ->minValue(1)
                            ->suffix(currency_code())
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(trans('app.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->suffix(' ' . currency_code())
                    ->label(trans('app.amount')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('app.created_at')),
            ])
            ->filters([
                //
            ])
            ->actions([
//                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markCompleted')
                    ->label('Mark as Completed')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    // [DASH-P2] Confirm the admin's own password before settling a payout, so a
                    // walked-away or hijacked session can't move money. current_password validates
                    // against the authenticated (web-guard) admin. (The settlement itself — the
                    // is_completed change on Transaction — is captured by AdminAuditObserver.)
                    ->form(static::passwordConfirmField())
                    ->color('success')
                    ->action(function ($record) {
                        $record->update(['is_completed' => 1]);
                    })
                    ->visible(fn($record) => $record->is_completed == 0),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulkMarkCompleted')
                        ->label(trans('app.mark_as_completed'))
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->form(static::passwordConfirmField())
                        ->action(function ( $records) {
                            foreach ($records as $record) {
                                if ($record->is_completed == 0) {
                                    $record->update(['is_completed' => 1]);
                                }
                            }
                        })
                        ->color('success'),
                ]),

            ]);
    }

    /** [DASH-P2] Modal field that re-confirms the acting admin's password before a money action. */
    protected static function passwordConfirmField(): array
    {
        return [
            TextInput::make('admin_password')
                ->label(trans('app.confirm_password'))
                ->password()
                ->required()
                ->dehydrated(false) // never persisted; just a gate
                ->rule('current_password'), // validates against the authenticated admin
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return Transaction::query()
            ->where('type', TransactionType::WITHDRAW->value)
            ->orderByDesc('created_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawTransactions::route('/'),
            'create' => Pages\CreateWithdrawTransaction::route('/create'),
            // [DASH-P1] No 'edit' route: a payout is immutable once created. The Edit page reused the
            // create form with NO balance re-check, letting an admin rewrite a completed payout's
            // amount/recipient and corrupt the artist's balance (which the API surfaces).
        ];
    }
}
