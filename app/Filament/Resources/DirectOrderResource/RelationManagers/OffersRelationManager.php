<?php

namespace App\Filament\Resources\DirectOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return trans('app.order_offers');
    }

    public function form(Form $form): Form
    {
        // [DASH-P1] Offers are artist bids/counter-offers created through the app; this panel is
        // read-only for them (see table() — no create/edit actions). The form is unused.
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        // [DASH-P1] Read-only. The old form was a single TextInput('order_id'), which let an admin
        // RE-PARENT an offer onto a different order (silently changing that order's cost/invoice),
        // and full Create/Edit/Delete let admins fabricate or erase financial offer rows with no
        // validation. Offers are driven by the API bidding flow, so admins only view them here.
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('artist.name')
                ->label(trans('app.artist')),
                Tables\Columns\TextColumn::make('counter_to')
                    ->label(trans('app.counter_to')),
                Tables\Columns\TextColumn::make('cost')
                    ->label(trans('app.cost')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('app.created_at')),
            ]);
    }
}
