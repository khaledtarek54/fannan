<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for SECURITY_ISSUES.md A1 — the Filament admin panel must only
 * be reachable by explicit admins, not every authenticated client/artist.
 */
class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_flagged_user_can_access_the_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $panel = Filament::getPanel('admin');

        $this->assertTrue($admin->fresh()->canAccessPanel($panel));
    }

    public function test_regular_client_or_artist_cannot_access_the_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertFalse(User::factory()->client()->create()->canAccessPanel($panel));
        $this->assertFalse(User::factory()->artist()->create()->canAccessPanel($panel));
    }
}
