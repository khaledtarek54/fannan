<?php

namespace App\Filament\Actions;

use Closure;
use Filament\Tables\Actions\Action;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [DASH-P3] Reusable "Export CSV" header action for admin lists. Streams the table's CURRENTLY
 * FILTERED rows (via getFilteredTableQuery) as a CSV download — Filament 3.3 has no native export
 * action, so this is a small dependency-free streaming action. Admin-panel only; reads only.
 *
 * $columns maps a CSV header to a closure that returns that cell's value for a record:
 *   ExportCsvAction::make(['Type' => fn ($r) => $r->type, 'Amount' => fn ($r) => $r->amount])
 */
class ExportCsvAction
{
    /** @param array<string, Closure> $columns */
    public static function make(array $columns, string $filename = 'export'): Action
    {
        return Action::make('exportCsv')
            ->label(trans('app.export'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(fn ($livewire): StreamedResponse => response()->streamDownload(
                fn () => self::writeCsv($livewire->getFilteredTableQuery()->lazy(), $columns),
                $filename . '.csv',
                ['Content-Type' => 'text/csv; charset=UTF-8'],
            ));
    }

    /**
     * Write the header row + one line per record to php://output. Kept public + separate from make()
     * so the CSV content is unit-testable via output buffering without wrestling a streamed response.
     *
     * @param iterable<\Illuminate\Database\Eloquent\Model> $records
     * @param array<string, Closure> $columns
     */
    public static function writeCsv(iterable $records, array $columns): void
    {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, array_keys($columns));

        foreach ($records as $record) {
            fputcsv($handle, array_map(
                fn (Closure $format) => self::cell($format($record)),
                array_values($columns),
            ));
        }

        fclose($handle);
    }

    /**
     * Turn a cell value into a CSV-safe string. Guards two things:
     *  - reusable-helper robustness: arrays / non-stringable objects are JSON-encoded instead of
     *    fataling with "Array to string conversion" mid-stream;
     *  - [SECURITY] CSV formula injection: exported values (names, etc.) are end-user-controlled, so a
     *    value starting with = + - @ tab/CR would be executed as a formula if the admin opens the CSV
     *    in Excel/Sheets. Prefix such values with an apostrophe to neutralize them.
     */
    private static function cell(mixed $value): string
    {
        if (is_null($value)) {
            return '';
        }
        if (is_array($value) || (is_object($value) && ! method_exists($value, '__toString'))) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $string = (string) $value;

        if ($string !== '' && in_array($string[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            $string = "'" . $string;
        }

        return $string;
    }
}
