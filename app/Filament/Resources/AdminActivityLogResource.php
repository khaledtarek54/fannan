<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminActivityLogResource\Pages;
use App\Models\AdminActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * [DASH-P2] Read-only view of the admin audit trail (admin_activity_logs). Attribution for
 * privileged panel actions. Never touches the mobile API.
 */
class AdminActivityLogResource extends Resource
{
    protected static ?string $model = AdminActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 9;

    public static function getNavigationGroup(): ?string
    {
        return __('app.users');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.activity_log');
    }

    public static function getModelLabel(): string
    {
        return __('app.activity_log_entry');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.activity_log');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return AdminActivityLog::query()->with('admin');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(trans('app.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('admin.name')
                    ->label(trans('app.admin'))
                    ->searchable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('event')
                    ->label(trans('app.event'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->label(trans('app.subject'))
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                Tables\Columns\TextColumn::make('auditable_id')
                    ->label('ID'),
                Tables\Columns\TextColumn::make('description')
                    ->label(trans('app.description'))
                    ->limit(60),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label(trans('app.event'))
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                    ]),
                Tables\Filters\SelectFilter::make('admin_id')
                    ->label(trans('app.admin'))
                    ->relationship('admin', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminActivityLogs::route('/'),
        ];
    }
}
