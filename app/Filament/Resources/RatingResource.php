<?php

namespace App\Filament\Resources;

use App\Filament\Filters\CreatedBetweenFilter;
use App\Filament\Resources\RatingResource\Pages;
use App\Models\Rating;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read + moderate artist reviews. Admins can view and delete abusive ratings; creation/editing
 * is not offered (ratings come from clients in the app).
 */
class RatingResource extends Resource
{
    protected static ?string $model = Rating::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('app.users');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.ratings');
    }

    public static function getModelLabel(): string
    {
        return __('app.rating');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.ratings');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('artist.name')
                    ->label(trans('app.artist'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label(trans('app.client'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('stars')
                    ->label(trans('app.stars'))
                    ->badge()
                    ->color(fn ($state) => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label(trans('app.message'))
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('app.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('stars')
                    ->label(trans('app.stars'))
                    ->options([5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']),
                SelectFilter::make('artist_id')
                    ->label(trans('app.artist'))
                    ->searchable()
                    ->options(fn () => User::artist()->pluck('name', 'id')),
                // [DASH-P3] filter by the reviewing client, by whether written feedback was left, and by date.
                SelectFilter::make('client_id')
                    ->label(trans('app.client'))
                    ->relationship('client', 'name')
                    ->searchable(),
                TernaryFilter::make('has_review')
                    ->label(trans('app.has_review'))
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('notes')->where('notes', '!=', ''),
                        false: fn (Builder $q) => $q->where(fn (Builder $q) => $q->whereNull('notes')->orWhere('notes', '')),
                        blank: fn (Builder $q) => $q,
                    ),
                CreatedBetweenFilter::make(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return Rating::query()->with(['artist', 'client']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRatings::route('/'),
        ];
    }
}
