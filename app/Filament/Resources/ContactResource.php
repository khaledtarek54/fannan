<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('app.supports');
    }

    public static function getNavigationLabel(): string
    {
        return __('front.contact');
    }

    public static function getModelLabel(): string
    {
        return __('front.contact');
    }

    public static function getPluralModelLabel(): string
    {
        return __('front.contact');
    }

    // Contact submissions arrive from the public website — they are inbound-only. The create/edit
    // forms were blank (all fields ->visibleOn('view')); disable creation and expose read + delete
    // only. The View action renders the fields in a modal from the form() below.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                ->label(trans('app.name'))
                ->visibleOn('view'),
                TextInput::make('email')
                    ->label(trans('app.email'))
                    ->visibleOn('view'),
                TextInput::make('phone')
                    ->label(trans('app.phone'))
                    ->visibleOn('view'),
                Textarea::make('message')
                    ->label(trans('front.details'))
                    ->visibleOn('view'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(trans('app.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(trans('app.email'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(trans('app.phone'))
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            // create/edit intentionally omitted — inbound submissions are read + delete only.
        ];
    }
}
