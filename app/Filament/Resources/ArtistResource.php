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
                            ->required(),
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
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required(),
                        Forms\Components\TextInput::make('password')
                            ->hiddenOn(['view', 'edit'])
                            ->required(),
                        Forms\Components\DatePicker::make('dob')
                            ->required(),
                        Forms\Components\Select::make('gender')
                            ->searchable()
                            // [DASH-P1] The gender column is enum('male','female') — offering 'other'
                            // errored on strict MySQL or stored '' and corrupted the record.
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ]),
                        Select::make('city_id')
                            ->label(trans('app.city'))
                            ->searchable()
                            ->options(City::get()->pluck('name', 'id')->toArray())
                            ->required(),
                        Forms\Components\TextInput::make('vat_number')
                            ->rules(['digits_between:1,16']),
                        Forms\Components\TextInput::make('cr_number')
                            ->rules(['digits_between:1,16']),
                        Forms\Components\TextInput::make('iban')
                            ->required()
                            ->rules([new Iban()]),
                        FileUpload::make('profile_photo')
                            ->label('Profile Photo')
                            ->required()
                            ->directory("users"),
                        PhoneInput::make('whatsapp')
                            ->nullable(),
                        Forms\Components\TextInput::make('instagram')
                            ->nullable(),
                        Forms\Components\TextInput::make('facebook')
                            ->nullable(),
                        Forms\Components\TextInput::make('snapchat')
                            ->nullable(),
                        // [DASH-P1] The column was renamed twiteer -> youtube (migration 2026_02_20)
                        // and only 'youtube' is in User::$fillable, so the old make('twiteer') field
                        // silently dropped the value. Map to the real, fillable column.
                        Forms\Components\TextInput::make('youtube')
                            ->label('X')
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
                Tables\Columns\TextColumn::make('city.name')
                    ->label(trans('app.city'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('platform_fees')
                    ->label(trans('app.setting.platform_fees'))
                    ->searchable(),
            ])
            ->filters([
                //
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
                        ->label('Edit Fees')
                        ->icon('heroicon-o-pencil')
                        ->form([
                            TextInputAlias::make('platform_fees')
                                ->label('New Value')
                                ->minValue(0)
                                ->integer()
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Edit Fees')
                        ->modalSubheading('Enter a new platform fees value.')
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
        return User::withTrashed()->where("role", UserRole::ARTIST->value);
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
