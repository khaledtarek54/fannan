<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards for docs/SECURITY_ISSUES_ROUND2.md:
 *  R2-C5 — phone-verification code has a TTL and a per-account attempt lockout (and there is no
 *          static 1234 backdoor / no more 4-digit rand()).
 *  R2-M6 — a user's existing tokens are revoked when the password is reset.
 */
class OtpSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function userWithCode(string $phone, int $code, $expiresAt): User
    {
        $user = User::factory()->create(['phone' => $phone]);
        $user->forceFill([
            'verification_code' => $code,
            'verification_code_expires_at' => $expiresAt,
            'verification_code_attempts' => 0,
        ])->save();

        return $user;
    }

    public function test_a_fresh_code_is_four_digits_with_a_future_expiry(): void
    {
        $user = User::factory()->create();

        $code = $user->freshVerificationCode();
        $user->save();

        $this->assertGreaterThanOrEqual(1000, $code);
        $this->assertLessThanOrEqual(9999, $code);
        $this->assertTrue($user->verification_code_expires_at->isFuture());
    }

    public function test_an_expired_code_is_rejected_on_password_reset(): void
    {
        $this->userWithCode('966500000021', 1234, now()->subMinute());

        $this->postJson('/api/password/update', [
            'phone' => '966500000021',
            'password' => 'newsecret',
            'verification_code' => 1234,
        ])->assertStatus(403);
    }

    public function test_the_code_is_burned_after_five_wrong_attempts(): void
    {
        $user = $this->userWithCode('966500000022', 1234, now()->addMinutes(10));

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/password/update', [
                'phone' => '966500000022',
                'password' => 'newsecret',
                'verification_code' => 9999, // wrong
            ])->assertStatus(403);
        }

        // The code is now invalidated — even the CORRECT code no longer resets the password.
        $this->postJson('/api/password/update', [
            'phone' => '966500000022',
            'password' => 'newsecret',
            'verification_code' => 1234,
        ])->assertStatus(403);

        $this->assertNull($user->fresh()->verification_code);
    }

    public function test_the_correct_code_within_ttl_resets_the_password(): void
    {
        $this->userWithCode('966500000023', 1234, now()->addMinutes(10));

        $this->postJson('/api/password/update', [
            'phone' => '966500000023',
            'password' => 'newsecret',
            'verification_code' => 1234,
        ])->assertStatus(200);
    }

    public function test_password_reset_revokes_existing_tokens(): void
    {
        $user = $this->userWithCode('966500000024', 1234, now()->addMinutes(10));
        $oldTokenId = $user->createToken('authToken')->token->id;

        $this->assertDatabaseHas('oauth_access_tokens', ['id' => $oldTokenId]);

        $this->postJson('/api/password/update', [
            'phone' => '966500000024',
            'password' => 'newsecret',
            'verification_code' => 1234,
        ])->assertStatus(200);

        // The pre-reset token row is gone (revoked via ->tokens()->delete()).
        $this->assertDatabaseMissing('oauth_access_tokens', ['id' => $oldTokenId]);
    }
}
