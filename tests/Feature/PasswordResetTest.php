<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regression guard for CODE_REVIEW_FINDINGS.md B3 — /password/update must require a
 * valid SMS verification code; previously any phone number could reset any password.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create(['phone' => '966500000001']);
        // [R2-C5] The code now needs a live TTL window to be accepted.
        $user->forceFill([
            'verification_code' => 1234,
            'verification_code_expires_at' => now()->addMinutes(10),
            'verification_code_attempts' => 0,
        ])->save();

        return $user;
    }

    public function test_reset_is_rejected_without_a_verification_code(): void
    {
        $this->makeUser();

        $this->postJson('/api/password/update', [
            'phone' => '966500000001',
            'password' => 'newsecret',
        ])->assertStatus(422); // verification_code required
    }

    public function test_reset_is_rejected_with_a_wrong_code(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/password/update', [
            'phone' => '966500000001',
            'password' => 'newsecret',
            'verification_code' => 9999,
        ])->assertStatus(403);

        $this->assertFalse(Hash::check('newsecret', $user->fresh()->password));
    }

    public function test_reset_succeeds_with_the_correct_code(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/password/update', [
            'phone' => '966500000001',
            'password' => 'newsecret',
            'verification_code' => 1234,
        ])->assertStatus(200);

        $this->assertTrue(Hash::check('newsecret', $user->fresh()->password));
    }
}
