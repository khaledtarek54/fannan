<?php

namespace App\Filament\Resources;

use App\Enums\SettingKey;
use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SettingResource extends Resource
{

    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.configurations');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.settings');
    }

    public static function getModelLabel(): string
    {
        return __('app.settings');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.settings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->schema([
                    TextInput::make('type_string')
                        ->label(trans('app.name'))
                        ->default(function ($record) {
                            return $record ? $record->type_string : '';
                        })
                        ->disabled(),
                    RichEditor::make('value')
                        ->label(trans('app.value'))
                        ->disableAllToolbarButtons()
                        ->enableToolbarButtons([
                            'attachFiles',
                            'blockquote',
                            'bold',
                            'bulletList',
                            'codeBlock',
                            'h2',
                            'h3',
                            'italic',
                            'link',
                            'orderedList',
                            'redo',
                            'strike',
                            'underline',
                            'undo',
                        ])
                        ->translatable(true, null, [
                            'en' => ['required'],
                            'ar' => ['required'],
                        ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type_string')
                    ->label(trans('app.type')),
                TextColumn::make('value')
                    ->label(trans('app.value'))
                    ->html()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
        return Setting::whereIn("type", [SettingKey::TERMS->value, SettingKey::PRIVACY->value, SettingKey::HELP_SUPPORT->value, SettingKey::ABOUT_US->value, SettingKey::ARTIST_ACKNOWLEDGEMENT->value,]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
