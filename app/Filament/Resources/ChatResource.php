<?php

namespace App\Filament\Resources;

use App\Filament\Filters\CreatedBetweenFilter;
use App\Filament\Resources\ChatResource\Pages;
use App\Models\Chat;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only chat viewer for support / abuse investigations. No editing — admins observe and,
 * if needed, delete a message.
 */
class ChatResource extends Resource
{
    protected static ?string $model = Chat::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('app.supports');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.chats');
    }

    public static function getModelLabel(): string
    {
        return __('app.chat');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.chats');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fromUser.name')
                    ->label(trans('app.sender'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('toUser.name')
                    ->label(trans('app.receiver'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(trans('app.type'))
                    ->badge(),
                Tables\Columns\TextColumn::make('message')
                    ->label(trans('app.message'))
                    ->searchable() // [DASH-P3] make message content searchable for moderation
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
                CreatedBetweenFilter::make(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return Chat::query()->with(['fromUser', 'toUser']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChats::route('/'),
        ];
    }
}
