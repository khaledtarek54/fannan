<?php

namespace App\Filament\Resources;

use App\Enums\ModelName;
use App\Filament\Resources\SupportResource\Pages;
use App\Models\Support;
use Carbon\Carbon;
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

class SupportResource extends Resource
{
    protected static ?string $model = Support::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.supports');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.supports');
    }

    public static function getModelLabel(): string
    {
        return __('app.support');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.supports');
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
                Action::make('mark_as_complete')
                    ->label(trans('app.mark_as_complete'))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        Support::query()->where('is_complete', 0)
                            ->update(['is_complete' => 1]);
                    }),
                Action::make('view')
                    ->label('View')
                    ->modalHeading('User Messages')
                    ->modalWidth(MaxWidth::ScreenLarge)
                    ->formId('chatContainer')
                    ->form([
                        TextInput::make('message')
                            ->label('Reply Message')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        Support::create([
                            'user_id' => $record->user_id,
                            'reply_user_id' => auth()->id(),
                            'description' => $data['message'],
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    })
                    ->modalContent(function ($record) {
                        $messages = $record->user->activeSupport;
                        return view('filament.resources.support-resource.messages-modal', compact('messages'));
                    }),
                Action::make('view_order')
                    ->label(trans('app.view_event'))
                    ->hidden(fn($record) => is_null($record->model_id))
                    ->url(function ($record) {
                        if ($record->model_type == ModelName::ORDER->value)
                            return url('admin/direct-orders/' . $record->model_id);
                        return url('admin/bidding-orders/'. $record->model_id);
                    })
                    ->openUrlInNewTab(),
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
//            ->whereNull('model_id')
            ->where('is_complete', 0)
            ->groupBy('user_id')
            ->with('user.activeSupport')
            ->orderByDesc('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupports::route('/'),
            'create' => Pages\CreateSupport::route('/create'),
            'edit' => Pages\EditSupport::route('/{record}/edit'),
        ];
    }
}
