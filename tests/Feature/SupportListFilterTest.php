<?php

namespace Tests\Feature;

use App\Filament\Resources\SupportResource\Pages\ListSupports;
use App\Models\Support;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The support-tickets list uses a MAX(id)-per-user grouped query; the shared date-range filter must
 * work on top of it. Admin-panel only; no API impact.
 */
class SupportListFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function openTicket(User $user, $createdAt): Support
    {
        $ticket = new Support(['user_id' => $user->id, 'description' => 'help', 'is_complete' => 0]);
        $ticket->created_at = $createdAt; // dirty timestamp is preserved on insert
        $ticket->save();

        return $ticket;
    }

    public function test_support_tickets_can_be_filtered_by_a_created_date_range(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $old = $this->openTicket(User::factory()->client()->create(), now()->subMonths(2));
        $recent = $this->openTicket(User::factory()->client()->create(), now());

        Livewire::test(ListSupports::class)
            ->filterTable('created_at', ['from' => now()->subDays(7)->toDateString()])
            ->assertCanSeeTableRecords([$recent])
            ->assertCanNotSeeTableRecords([$old]);
    }
}
