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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                                fn($get) => new UniquePhoneNumber( $get('recordId')),
                            ]),
                        TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required(),
                        TextInput::make('password')
                            ->hiddenOn(['view'])
                            ->minValue(6)
                            ->required(fn($context) => $context === 'create'),
                        DatePicker::make('dob')
                            ->required(),
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
                Tables\Columns\TextColumn::make('dob')->date(),
            ])
            ->
            filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make(trans('app.restore'))
                    ->visible(fn(User $user) => $user->deleted_at)
                    ->action(function (array $data, User $user) {
                        $user->update(['deleted_at' => null]);
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
        return User::withTrashed()->where('role', 'admin');
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
