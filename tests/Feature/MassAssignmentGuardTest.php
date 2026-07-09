<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for docs/SECURITY_ISSUES_ROUND2.md R2-C4 — Model::unguard() was removed, so
 * every model's $fillable is authoritative again and privilege columns cannot be mass-assigned.
 */
class MassAssignmentGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_are_not_globally_unguarded(): void
    {
        $this->assertFalse(Model::isUnguarded(), 'Model::unguard() must not be active app-wide.');
    }

    public function test_is_admin_cannot_be_mass_assigned_on_a_user(): void
    {
        $user = User::factory()->create();

        // Attempt to self-escalate the way any $request->all() sink would.
        $user->update(['is_admin' => true]);

        $this->assertFalse((bool) $user->fresh()->is_admin);
    }

    public function test_profile_columns_city_id_and_iban_remain_fillable(): void
    {
        // Guards the R2-C4 blast-radius fix: city_id and iban are written via mass assignment by
        // the profile-completion flow and the admin panel, so they must stay fillable.
        $user = User::factory()->create();

        $user->update(['city_id' => '7', 'iban' => 1234567890]);

        $fresh = $user->fresh();
        $this->assertSame('7', (string) $fresh->city_id);
        $this->assertSame(1234567890, (int) $fresh->iban);
    }
}
