<?php

namespace App\Filament\Resources;

use App\Enums\AdStatus;
use App\Filament\Resources\AdResource\Pages;
use App\Models\Ad;
use App\Models\Category;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdResource extends Resource
{
    protected static ?string $model = Ad::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.promotions');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.ads');
    }

    public static function getModelLabel(): string
    {
        return __('app.ad');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.ads');
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
                            ->required(),
                        FileUpload::make('image')
                            ->label(trans('app.photo'))
                            ->required()
                            ->directory("ads"),
                        Forms\Components\TextInput::make('link')
                            ->label(trans('app.link')),
                        Forms\Components\MorphToSelect::make('adable')
                            ->label(trans('app.relation'))
                            ->types([
                                Forms\Components\MorphToSelect\Type::make(User::class)
                                    ->modifyOptionsQueryUsing(fn (Builder $query) => $query->where('role', 'artist'))
                                    ->titleAttribute('name'),
                                Forms\Components\MorphToSelect\Type::make(Category::class)
                                    ->titleAttribute('name'),
                            ])
                            ->searchable()
                            ->preload(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(trans('app.name'))
                    ->searchable(),
                ImageColumn::make('image')->circular()
                    ->label(trans('app.photo')),
                Tables\Columns\TextColumn::make('link')
                    ->label(trans('app.link'))
                    ->searchable(),
                BadgeColumn::make('status')
                    ->label(trans('app.status'))
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(function ($state) {
                        return $state === 'active' ? 'Active' : 'Inactive';
                    }),
                TextColumn::make('created_at')
                    ->dateTime(),
                TextColumn::make('adable.name'),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('active')
                    ->label(__('app.active'))
                    ->visible(fn(Ad $ad) => $ad->status == "inactive")
                    ->action(function (array $data, Ad $ad) {
                        $ad->setStatus(AdStatus::ACTIVE->value);
                    }),
                Tables\Actions\Action::make('inactive')
                    ->label(__('app.inactive'))
                    ->visible(fn(Ad $ad) => $ad->status == "active")
                    ->action(function (array $data, Ad $ad) {
                        $ad->setStatus(AdStatus::INACTIVE->value);
                    }),
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
            'index' => Pages\ListAds::route('/'),
            'create' => Pages\CreateAd::route('/create'),
            'edit' => Pages\EditAd::route('/{record}/edit'),
        ];
    }
}
