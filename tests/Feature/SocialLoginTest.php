<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirebaseAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for docs/SECURITY_ISSUES_ROUND2.md R2-C1 — the social-login auth bypass.
 *
 * Before the fix, POST /api/login-social {"email": "victim@example.com"} returned a valid
 * Passport bearer token for the victim (no password, no OTP). The endpoint must now trust ONLY
 * a server-verified Firebase ID token and resolve the account from the token's verified email.
 */
class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    /** Replace the Firebase verifier with a fake that returns $email (null = token invalid). */
    private function fakeVerifier(?string $email): void
    {
        $this->mock(FirebaseAuthService::class, function ($mock) use ($email) {
            $mock->shouldReceive('verifiedEmail')->andReturn($email);
        });
    }

    public function test_email_only_request_is_rejected_old_bypass_is_closed(): void
    {
        User::factory()->create(['email' => 'victim@example.com']);

        // The pre-fix exploit payload — an email with no ID token — must now fail validation
        // instead of returning a token for the victim.
        $this->postJson('/api/login-social', ['email' => 'victim@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('id_token');
    }

    public function test_invalid_token_is_rejected_without_issuing_a_token(): void
    {
        User::factory()->create(['email' => 'victim@example.com']);
        $this->fakeVerifier(null); // verification fails / forged token

        $this->postJson('/api/login-social', ['id_token' => 'forged.token.value'])
            ->assertStatus(400)
            ->assertJsonPath('data', null); // no user, no token handed back
    }

    public function test_account_is_resolved_from_the_verified_email_not_the_request_body(): void
    {
        $victim = User::factory()->create(['email' => 'victim@example.com']);
        // Token verifies as the victim; the attacker also passes a different email in the body,
        // which must be ignored entirely.
        $this->fakeVerifier('victim@example.com');

        $response = $this->postJson('/api/login-social', [
            'id_token' => 'valid-victim-token',
            'email' => 'attacker@example.com',
        ])->assertStatus(200);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertSame($victim->id, $response->json('data.user.id'));
    }

    public function test_valid_token_for_unknown_email_is_rejected_and_not_auto_registered(): void
    {
        $this->fakeVerifier('nobody@example.com'); // genuinely verified, but no such account

        $this->postJson('/api/login-social', ['id_token' => 'valid-but-unknown'])
            ->assertStatus(400);

        // Login-only: a verified token must not create an account.
        $this->assertDatabaseMissing('users', ['email' => 'nobody@example.com']);
    }

    public function test_unverified_account_cannot_social_login(): void
    {
        User::factory()->unverified()->create(['email' => 'pending@example.com']);
        $this->fakeVerifier('pending@example.com');

        $this->postJson('/api/login-social', ['id_token' => 'valid'])
            ->assertStatus(400);
    }

    public function test_blocked_account_cannot_social_login(): void
    {
        $user = User::factory()->create(['email' => 'blocked@example.com']);
        $user->delete(); // soft-deleted = blocked
        $this->fakeVerifier('blocked@example.com');

        $this->postJson('/api/login-social', ['id_token' => 'valid'])
            ->assertStatus(400);
    }
}
