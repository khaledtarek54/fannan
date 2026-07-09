<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * The admin audit trail records who did what in the panel. The observer must log ONLY when a panel
 * admin (is_admin, web guard) is acting — so mobile-API traffic (Passport 'api' guard) is never
 * logged or affected — and must never write secrets into the log.
 */
class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_a_panel_admin_action_is_recorded(): void
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

    public function test_an_admin_authenticated_on_the_api_guard_is_not_logged(): void
    {
        // Mobile API: an admin (admins share the users table + can log in via the app) authenticates
        // on the 'api' guard, and auth:api flips the DEFAULT guard to 'api' mid-request. The observer
        // must still not log — it gates on the web (session) guard, which a token request never sets.
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'api');
        Auth::shouldUse('api'); // what the auth:api middleware does after authenticating

        Transaction::factory()->income()->create(['amount' => 100]);
        Setting::create(['type' => 'demo_api', 'value' => ['en' => '1', 'ar' => '1']]);

        $this->assertSame(0, AdminActivityLog::count(), 'API-guard admin actions must never be logged');
    }

    public function test_a_non_admin_actor_is_not_logged(): void
    {
        $client = User::factory()->client()->create();
        $this->actingAs($client); // authenticated but not an admin

        Setting::create(['type' => 'demo_key2', 'value' => ['en' => '1', 'ar' => '1']]);

        $this->assertSame(0, AdminActivityLog::count());
    }

    public function test_sensitive_fields_are_stripped_from_the_log(): void
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
}
