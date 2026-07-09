<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Filament\Actions\ExportCsvAction;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Models\Transaction;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CSV export of admin finance lists (Transactions ledger, Invoices). Admin-panel only; read-only.
 */
class ExportCsvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /** capture what ExportCsvAction::writeCsv streams to php://output */
    private function csv(iterable $records, array $columns): string
    {
        ob_start();
        ExportCsvAction::writeCsv($records, $columns);
        return ob_get_clean();
    }

    public function test_it_writes_a_header_row_and_one_line_per_record(): void
    {
        $artist = User::factory()->artist()->create(['name' => 'Sara']);
        Transaction::factory()->income()->create(['user_id' => $artist->id, 'amount' => 42]);

        $csv = $this->csv(Transaction::query()->get(), [
            'User' => fn ($r) => $r->user?->name,
            'Type' => fn ($r) => $r->type,
            'Amount' => fn ($r) => $r->amount,
        ]);

        $this->assertStringContainsString('User,Type,Amount', $csv);
        $this->assertStringContainsString('Sara', $csv);
        $this->assertStringContainsString(TransactionType::INCOME->value, $csv);
        $this->assertStringContainsString('42', $csv);
    }

    public function test_the_csv_starts_with_a_utf8_bom_so_excel_renders_arabic(): void
    {
        Transaction::factory()->income()->create();

        $csv = $this->csv(Transaction::query()->get(), ['Type' => fn ($r) => $r->type]);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'CSV must start with a UTF-8 BOM');
    }

    public function test_negative_numbers_are_not_quoted_as_text(): void
    {
        Transaction::factory()->income()->create();

        // A bare negative number is never a spreadsheet formula, so it must NOT get a leading apostrophe.
        $csv = $this->csv(Transaction::query()->get(), ['Amount' => fn ($r) => -500]);

        $this->assertStringContainsString('-500', $csv);
        $this->assertStringNotContainsString("'-500", $csv);
    }

    public function test_null_values_become_empty_cells(): void
    {
        Transaction::factory()->income()->create(['amount' => 10]);

        $csv = $this->csv(Transaction::query()->get(), [
            'Missing' => fn ($r) => null,
            'Amount' => fn ($r) => $r->amount,
        ]);

        // header + one data row; the null column renders as an empty leading cell.
        $this->assertStringContainsString('Missing,Amount', $csv);
        $this->assertStringContainsString(',10', $csv);
    }

    public function test_it_neutralizes_spreadsheet_formula_injection(): void
    {
        // A client-controlled name that a spreadsheet would treat as a formula must be defused.
        $artist = User::factory()->artist()->create(['name' => '=HYPERLINK("http://evil.tld")']);
        Transaction::factory()->income()->create(['user_id' => $artist->id]);

        $csv = $this->csv(Transaction::query()->get(), [
            'User' => fn ($r) => $r->user?->name,
        ]);

        $this->assertStringContainsString("'=HYPERLINK", $csv, 'a formula-like name must be prefixed with an apostrophe');
    }

    public function test_array_values_are_json_encoded_instead_of_fataling(): void
    {
        Transaction::factory()->income()->create();

        // A closure returning an array (e.g. a future translatable column) must not throw mid-stream.
        $csv = $this->csv(Transaction::query()->get(), [
            'Meta' => fn ($r) => ['k' => 'v'],
        ]);

        $this->assertStringContainsString('{', $csv);
    }

    public function test_the_transactions_ledger_boots_with_the_export_action(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Transaction::factory()->income()->create();

        Livewire::test(ListTransactions::class)->assertSuccessful();
    }
}
