<?php

namespace App\Filament\Resources;

use App\Enums\Roles;
use App\Enums\UserRole;
use App\Models\City;
use App\Models\User;
use App\Rules\Iban;
use App\Rules\UniquePhoneNumber;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput as TextInputAlias;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;
use Filament\Tables\Actions\BulkAction;

class ArtistResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'entypo-users';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('app.users');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.active_artists');
    }

    public static function getModelLabel(): string
    {
        return __('app.artist');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.active_artists');
    }

    public static function getNavigationBadge(): ?string
    {
        return User::artist()->count();
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
                                fn($get) => new UniquePhoneNumber($get('recordId')),
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
                            // [DASH-P1] The gender column is enum('male','female') — offering 'other'
                            // errored on strict MySQL or stored '' and corrupted the record.
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
                        Forms\Components\TextInput::make('iban')
                            ->label(trans('app.iban'))
                            ->required()
                            ->rules([new Iban()]),
                        FileUpload::make('profile_photo')
                            ->label(trans('app.photo'))
                            ->required()
                            ->directory("users"),
                        PhoneInput::make('whatsapp')
                            ->nullable(),
                        Forms\Components\TextInput::make('instagram')
                            ->maxLength(512)
                            ->nullable(),
                        Forms\Components\TextInput::make('facebook')
                            ->maxLength(512)
                            ->nullable(),
                        Forms\Components\TextInput::make('snapchat')
                            ->maxLength(512)
                            ->nullable(),
                        // The DB column is named `youtube` (renamed from `twiteer` in migration
                        // 2026_02_20) but it holds the artist's X / Twitter handle — the column name is
                        // a leftover misnomer we can't rename without an API-affecting migration. The
                        // label 'X' is the correct, current platform name.
                        Forms\Components\TextInput::make('youtube')
                            ->label('X')
                            ->maxLength(512)
                            ->nullable(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(trans('app.name'))->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(trans('app.email'))
                    ->searchable(),
                PhoneColumn::make('phone')
                    ->label(trans('app.phone'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('dob')
                    ->label(trans('app.dob'))->date(),
                Tables\Columns\TextColumn::make('gender')
                    ->label(trans('app.gender'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('cityRelation.name')
                    ->label(trans('app.city'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('platform_fees')
                    ->label(trans('app.setting.platform_fees'))
                    ->searchable(),
                // [DASH-P3] surface the artist's review + order volume at a glance.
                Tables\Columns\TextColumn::make('ratings_count')
                    ->label(trans('app.ratings'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('artist_orders_count')
                    ->label(trans('app.orders'))
                    ->sortable(),
            ])
            ->filters([
                // [DASH-P3] the artist list had no filters at all. (Trashed/active is already covered by
                // the Active/Deactivate tabs, so filter by the things the tabs don't: gender, city.)
                Tables\Filters\SelectFilter::make('gender')
                    ->label(trans('app.gender'))
                    ->options(['male' => trans('app.male'), 'female' => trans('app.female')]),
                Tables\Filters\SelectFilter::make('city_id')
                    ->label(trans('app.city'))
                    ->searchable()
                    ->options(fn () => City::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->icon(null)
                    ->visible(fn(User $user) => !$user->deleted_at),
                Tables\Actions\DeleteAction::make()->icon(null)
                    ->visible(fn(User $user) => !$user->deleted_at),
                Tables\Actions\ViewAction::make()->icon(null),
                Tables\Actions\Action::make(trans('app.restore'))->icon(null)
                    ->visible(fn(User $user) => $user->deleted_at)
                    ->action(function (array $data, User $user) {
                        // [SECURITY][R2-C4] use SoftDeletes restore() — mass-assigning deleted_at
                        // no longer works now that unguard() is removed (it isn't in $fillable).
                        $user->restore();
                    })->requiresConfirmation(),
                Tables\Actions\Action::make('platform_fees')
                    ->label(trans('app.update_fees'))
                    ->icon(null)
                    ->form([
                        Forms\Components\TextInput::make('platform_fees')
                            ->label(trans('app.setting.platform_fees'))
                            // this single-record action had NO validation (the bulk one did) — a
                            // non-numeric entry reached the `double` column. Match the bulk rules.
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->default(fn(User $user) => $user->platform_fees) // Set the current value
                    ])
                    ->action(function (array $data, User $user) {
                        $user->update(['platform_fees' => $data['platform_fees']]);
                    })->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('edit_fees')
                        ->label(trans('app.edit_fees'))
                        ->icon('heroicon-o-pencil')
                        ->form([
                            TextInputAlias::make('platform_fees')
                                ->label(trans('app.new_value'))
                                ->minValue(0)
                                // ->numeric() not ->integer(): platform_fees is a `double`, so a
                                // fractional fee (e.g. 12.5) must be allowed.
                                ->numeric()
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading(trans('app.edit_fees'))
                        ->modalSubheading(trans('app.new_value'))
                        ->action(function ($records, array $data) {
                            $platformFees = $data['platform_fees'];
                            foreach ($records as $record) {
                                $record->platform_fees = $platformFees;
                                $record->save();
                            }
                        }),
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
        // [DASH-P3] load review + order counts in one query for the list columns (no N+1).
        return User::withTrashed()->where("role", UserRole::ARTIST->value)
            ->withCount(['ratings', 'artistOrders']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ArtistResource\Pages\ListArtists::route('/'),
            'create' => ArtistResource\Pages\CreateArtist::route('/create'),
            'edit' => ArtistResource\Pages\EditArtist::route('/{record}/edit'),
        ];
    }
}
