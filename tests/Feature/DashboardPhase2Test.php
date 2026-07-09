<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Filament\Resources\WithdrawTransactionResource\Pages\ListWithdrawTransactions;
use App\Models\AdminActivityLog;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Dashboard Phase 2 — admin audit trail + password-confirmation on money actions. Admin-panel only;
 * the audit log is a separate table the mobile API never reads or writes, and the observer only logs
 * when a panel admin (is_admin, web guard) is acting — so API traffic is never logged or affected.
 */
class DashboardPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    // ---- Admin audit trail -----------------------------------------------------

    public function test_a_panel_admin_action_is_recorded_in_the_audit_log(): void
    {
        $target = Transaction::factory()->income()->create(['amount' => 100]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin); // default (web) guard = the panel admin
        $target->update(['amount' => 250]);

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'event' => 'updated',
            'auditable_type' => Transaction::class,
            'auditable_id' => $target->id,
        ]);
        $log = AdminActivityLog::where('auditable_id', $target->id)->latest('id')->first();
        $this->assertArrayHasKey('amount', $log->properties);
    }

    public function test_actions_without_an_authenticated_admin_are_not_logged(): void
    {
        // Mimics mobile-API / unauthenticated model changes — must never be logged.
        Transaction::factory()->income()->create(['amount' => 100]);
        Setting::create(['type' => 'demo_key', 'value' => ['en' => '1', 'ar' => '1']]);

        $this->assertSame(0, AdminActivityLog::count());
    }

    public function test_a_non_admin_actor_is_not_logged(): void
    {
        $client = User::factory()->client()->create();
        $this->actingAs($client); // authenticated but not an admin

        Setting::create(['type' => 'demo_key2', 'value' => ['en' => '1', 'ar' => '1']]);

        $this->assertSame(0, AdminActivityLog::count());
    }

    public function test_sensitive_fields_are_stripped_from_the_audit_properties(): void
    {
        $user = User::factory()->client()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);
        $user->update(['password' => 'a-new-secret', 'name' => 'Renamed']);

        $log = AdminActivityLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('event', 'updated')
            ->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->properties, 'password must never be written to the audit log');
        $this->assertArrayHasKey('name', $log->properties);
    }

    // ---- Password confirmation on money actions --------------------------------

    public function test_settling_a_payout_requires_the_admins_password(): void
    {
        $admin = User::factory()->admin()->create(); // factory password is 'password'
        $artist = User::factory()->artist()->create();
        $payout = Transaction::factory()->withdraw()->create([
            'user_id' => $artist->id,
            'amount' => 50,
            'is_completed' => 0,
        ]);

        $this->actingAs($admin);

        // Wrong password → action is blocked, payout stays pending.
        Livewire::test(ListWithdrawTransactions::class)
            ->callTableAction('markCompleted', $payout, data: ['admin_password' => 'not-the-password'])
            ->assertHasTableActionErrors(['admin_password']);
        $this->assertSame(0, (int) $payout->fresh()->is_completed);

        // Correct password → payout is settled.
        Livewire::test(ListWithdrawTransactions::class)
            ->callTableAction('markCompleted', $payout, data: ['admin_password' => 'password'])
            ->assertHasNoTableActionErrors();
        $this->assertSame(1, (int) $payout->fresh()->is_completed);
    }
}
