<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\ClientResource\Pages;
use App\Models\City;
use App\Models\User;
use App\Rules\UniquePhoneNumber;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class ClientResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('app.users');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.active_clients');
    }

    public static function getModelLabel(): string
    {
        return __('app.client');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.active_clients');
    }

    public static function getNavigationBadge(): ?string
    {
        return User::client()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
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
                        Forms\Components\TextInput::make('email')
                            ->label(trans('app.email'))
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->required(),
                        // Mirror UserResource: mask the password, allow reveal, enforce a minimum
                        // length, and let an admin optionally reset it on edit. User::setPasswordAttribute()
                        // always Hash::make()s whatever it receives, so keep an empty submit out of the
                        // payload (filled()) — a blank field must not overwrite the existing password.
                        Forms\Components\TextInput::make('password')
                            ->label(trans('app.password'))
                            ->password()
                            ->revealable()
                            ->minLength(6)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn ($context) => $context === 'create')
                            ->hiddenOn(['view']),
                        Forms\Components\DatePicker::make('dob')
                            ->label(trans('app.dob'))
                            ->native(false)
                            ->maxDate(now()) // a birthdate can't be in the future
                            ->required(),
                        Forms\Components\Select::make('gender')
                            ->label(trans('app.gender'))
                            ->searchable()
                            // [DASH-P1] gender column is enum('male','female'); 'other' corrupted the row.
                            ->options([
                                'male' => trans('app.male'),
                                'female' => trans('app.female'),
                            ]),
                        Select::make('city_id')
                            ->label(trans('app.city'))
                            ->searchable()
                            ->options(City::pluck('name', 'id')->toArray())
                            ->required(),
                        Forms\Components\TextInput::make('vat_number')
                            ->label(trans('app.vat_number'))
                            ->rules(['digits_between:1,16']),
                        Forms\Components\TextInput::make('cr_number')
                            ->label(trans('app.cr_number'))
                            ->rules(['digits_between:1,16']),
                        FileUpload::make('profile_photo')
                            ->label(trans('app.photo'))
                            ->required()
                            ->directory("users"),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        // No ->query() override here: it used to shadow getEloquentQuery() and dropped withTrashed(),
        // so soft-deleted clients never appeared and the Restore action below was dead. The resource
        // query (User::withTrashed()->client()) plus the TrashedFilter now drive the list.
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(trans('app.name'))->searchable(),
                Tables\Columns\TextColumn::make('email')->label(trans('app.email'))->searchable(),
                PhoneColumn::make('phone')->label(trans('app.phone'))->searchable(),
                Tables\Columns\TextColumn::make('dob')->label(trans('app.dob'))->date(),
                Tables\Columns\TextColumn::make('gender')->label(trans('app.gender'))->searchable(),
                Tables\Columns\TextColumn::make('cityRelation.name')->label(trans('app.city'))->searchable(),
                // [DASH-P3] the client's order (event) volume at a glance.
                Tables\Columns\TextColumn::make('client_orders_count')->label(trans('app.orders'))->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                // [DASH-P3] filter clients by gender + city (was only a Trashed filter).
                Tables\Filters\SelectFilter::make('gender')
                    ->label(trans('app.gender'))
                    ->options(['male' => trans('app.male'), 'female' => trans('app.female')]),
                Tables\Filters\SelectFilter::make('city_id')
                    ->label(trans('app.city'))
                    ->searchable()
                    ->options(fn () => City::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(User $user) => !$user->deleted_at),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(User $user) => !$user->deleted_at),
                Tables\Actions\ViewAction::make(),
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // [DASH-P3] load the client's order count for the list column (no N+1).
        return User::withTrashed()->client()->withCount('clientOrders');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
