<?php

namespace App\Filament\Resources\CouponResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Who redeemed this coupon and when. Read-only usage report.
 */
class CouponUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'couponUsers';

    protected static ?string $title = 'Usage';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(trans('app.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('app.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc');
    }
}
