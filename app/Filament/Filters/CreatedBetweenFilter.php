<?php

namespace App\Filament\Filters;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * [DASH-P3] Reusable "created between" date-range filter for the read-only admin lists (ledger,
 * ratings, chats, notifications, support). One definition instead of copy-pasting from/until pickers
 * into every resource. Admin-panel only.
 */
class CreatedBetweenFilter
{
    public static function make(string $column = 'created_at'): Filter
    {
        return Filter::make($column)
            ->form([
                DatePicker::make('from')->label(trans('app.from_date')),
                DatePicker::make('until')->label(trans('app.until_date')),
            ])
            // Note: whereDate compares on the DB's stored (UTC) calendar day, so boundary rows within
            // a few hours of midnight can fall on the adjacent day vs the admin's local date. Acceptable
            // for a read-only reporting filter; inherent to whereDate on UTC timestamps.
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate($column, '>=', $d))
                ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate($column, '<=', $d)))
            ->indicateUsing(function (array $data) {
                $indicators = [];
                if ($data['from'] ?? null) {
                    $indicators[] = trans('app.from_date') . ': ' . $data['from'];
                }
                if ($data['until'] ?? null) {
                    $indicators[] = trans('app.until_date') . ': ' . $data['until'];
                }
                return $indicators;
            });
    }
}
