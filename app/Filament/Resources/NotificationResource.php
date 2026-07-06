<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only view of notifications sent to users (visibility only). Composing and sending a
 * broadcast push is a separate follow-up — it needs the PushNotification payload wired up and
 * the Notification model's key-based title/body reworked to accept free text.
 */
class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('app.supports');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.notifications');
    }

    public static function getModelLabel(): string
    {
        return __('app.notification');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.notifications');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('toUser.name')
                    ->label(trans('app.receiver'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(trans('app.title'))
                    ->limit(40),
                Tables\Columns\TextColumn::make('body')
                    ->label(trans('app.body'))
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_read')
                    ->label(trans('app.read'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('app.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_read')
                    ->label(trans('app.read')),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return Notification::query()->with(['user', 'toUser']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}
