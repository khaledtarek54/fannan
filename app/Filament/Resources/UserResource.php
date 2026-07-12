<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Rules\UniquePhoneNumber;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'fas-users-gear';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.users');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.admins');
    }

    public static function getModelLabel(): string
    {
        return __('app.admin');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.admins');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(trans('app.name'))
                            ->required()
                            ->maxLength(255),
                        PhoneInput::make('phone')
                            ->required()
                            ->countryStatePath('country_code')
                            ->separateDialCode(false)
                            ->unique(ignoreRecord: true)
                            ->afterStateHydrated(function ($state, $livewire, $set) {
                                $recordId = $livewire->record->id ?? null;
                                $set('recordId', $recordId);
                            })
                            ->rules([
                                fn($get) => new UniquePhoneNumber( $get('recordId')),
                            ]),
                        TextInput::make('email')
                            ->label(trans('app.email'))
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->required(),
                        TextInput::make('password')
                            ->label(trans('app.password'))
                            ->password()
                            ->revealable()
                            ->minLength(6) // was ->minValue(6): a numeric rule that never fired on a string
                            // Do not persist an empty value on edit. User::setPasswordAttribute() always
                            // Hash::make()s whatever it receives, so a blank submit used to overwrite the
                            // admin's password with hash('') and lock them out. filled() keeps it out of
                            // the payload unless a new password was actually typed.
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn($context) => $context === 'create')
                            ->hiddenOn(['view']),
                        DatePicker::make('dob') // [DASH-P1] optional — was a needless create blocker for an admin account
                            ->label(trans('app.dob'))
                            ->native(false)
                            ->maxDate(now()), // a birthdate can't be in the future
//                        Select::make('gender')
//                            ->options([
//                                'male' => 'Male',
//                                'female' => 'Female',
//                                'other' => 'Prefer not to tell',
//                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(trans('app.name'))->searchable(),
                Tables\Columns\TextColumn::make('email')->label(trans('app.email'))->searchable(),
                PhoneColumn::make('phone')->label(trans('app.phone'))->searchable(),
                Tables\Columns\TextColumn::make('dob')->label(trans('app.dob'))->date(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    // [DASH-P1] Never let an admin delete themselves or the last active admin —
                    // that would lock everyone out of the panel with SQL-only recovery.
                    ->before(function (Tables\Actions\DeleteAction $action, User $record) {
                        if ($record->id === auth()->id()) {
                            Notification::make()->title('You cannot delete your own admin account.')->danger()->send();
                            $action->halt();
                        } elseif (static::activeAdminCount() <= 1) {
                            Notification::make()->title('You cannot delete the last remaining admin.')->danger()->send();
                            $action->halt();
                        }
                    }),
                Tables\Actions\Action::make(trans('app.restore'))
                    ->visible(fn(User $user) => $user->deleted_at)
                    ->action(function (array $data, User $user) {
                        // [SECURITY][R2-C4] use SoftDeletes restore() — mass-assigning deleted_at
                        // no longer works now that unguard() is removed (it isn't in $fillable).
                        $user->restore();
                    })->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                            if ($records->contains(fn (User $u) => $u->id === auth()->id())) {
                                Notification::make()->title('Your selection includes your own admin account — deselect it first.')->danger()->send();
                                $action->halt();
                            } elseif (static::activeAdminCount() - $records->whereNull('deleted_at')->count() < 1) {
                                Notification::make()->title('That would delete every remaining admin and lock everyone out.')->danger()->send();
                                $action->halt();
                            }
                        }),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    /** [DASH-P1] Active (non-trashed) admins — the accounts that can actually reach the panel. */
    protected static function activeAdminCount(): int
    {
        return User::where('is_admin', true)->whereNull('deleted_at')->count();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // [DASH-P1] List by is_admin (the real panel gate), not role='admin'. role defaults to
        // 'admin' for every user at the DB level, so the old filter both showed non-admins and
        // could hide a genuine is_admin account whose role differs.
        return User::withTrashed()->where('is_admin', true);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
