<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompletedSupportResource\Pages;
use App\Filament\Resources\CompletedSupportResource\RelationManagers;
use App\Models\CompletedSupport;
use App\Models\Support;
use Carbon\Carbon;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompletedSupportResource extends Resource
{
    protected static ?string $model = Support::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('app.supports');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.completed_tickets');
    }

    public static function getModelLabel(): string
    {
        return __('app.support');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.completed_tickets');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->columns(2)->schema([
                    TextInput::make('name')
                        ->label(trans('app.name'))
                        ->required(),
                    TextInput::make('phone')
                        ->label(trans('app.phone'))
                        ->required(),
                    TextInput::make('email')
                        ->label(trans('app.email'))
                        ->email()
                        ->required(),
                    Forms\Components\Textarea::make('description')
                        ->label(trans('app.description'))
                        ->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(trans('app.name'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(trans('app.phone'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(trans('app.email'))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(trans('app.description'))
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->modalHeading('User Messages')
                    ->modalWidth(MaxWidth::ScreenLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn (StaticAction $action) => $action->label('Close'))
                    ->modalContent(function ($record) {
                        $messages = $record->user->supports;
                        return view('filament.resources.support-resource.messages-modal', compact('messages'));
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
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return Support::query()
            ->whereNull('model_id')
            ->where('is_complete', 1)
            ->groupBy('user_id')
            ->with('user.supports')
            ->orderByDesc('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompletedSupports::route('/'),
            'create' => Pages\CreateCompletedSupport::route('/create'),
            'edit' => Pages\EditCompletedSupport::route('/{record}/edit'),
        ];
    }
}
