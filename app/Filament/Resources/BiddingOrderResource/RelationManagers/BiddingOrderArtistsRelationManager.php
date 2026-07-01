<?php

namespace App\Filament\Resources\BiddingOrderResource\RelationManagers;

use App\Models\BiddingOrderArtist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BiddingOrderArtistsRelationManager extends RelationManager
{
    protected static string $relationship = 'biddingOrderArtists';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return trans('app.bidding_order_artists_offers');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('artist_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BiddingOrderArtist::query()->orderByDesc('is_accepted'))
            ->recordTitleAttribute('artist_id')
            ->columns([
                TextColumn::make('artist.name')
                    ->label(trans('app.artist')),
                Tables\Columns\TextColumn::make('subcategory.name')
                    ->label(trans('app.subcategory')),
                TextColumn::make('cost')
                    ->label(trans('app.cost')),
                Tables\Columns\IconColumn::make('is_accepted')
                    ->label(trans('app.is_accepted'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(trans('app.created_at'))
                    ->date(),
            ]);
    }
}
