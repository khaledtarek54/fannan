<?php

namespace Tests\Feature;

use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Models\Transaction;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Filtering the read-only transactions ledger (also exercises the shared CreatedBetweenFilter).
 * Admin-panel only; no API impact.
 */
class TransactionLedgerFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_ledger_can_be_filtered_by_a_created_date_range(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $old = Transaction::factory()->income()->create(['created_at' => now()->subMonths(2)]);
        $recent = Transaction::factory()->income()->create(['created_at' => now()]);

        Livewire::test(ListTransactions::class)
            ->filterTable('created_at', ['from' => now()->subDays(7)->toDateString()])
            ->assertCanSeeTableRecords([$recent])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_ledger_can_be_filtered_by_user(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $artistA = User::factory()->artist()->create();
        $artistB = User::factory()->artist()->create();
        $txA = Transaction::factory()->income()->create(['user_id' => $artistA->id]);
        $txB = Transaction::factory()->income()->create(['user_id' => $artistB->id]);

        Livewire::test(ListTransactions::class)
            ->filterTable('user_id', $artistA->id)
            ->assertCanSeeTableRecords([$txA])
            ->assertCanNotSeeTableRecords([$txB]);
    }
}
