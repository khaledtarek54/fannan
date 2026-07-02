<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard for SECURITY_ISSUES.md M7 — the public account-deletion form must require a valid
 * verification code, so an account can't be deleted with just a phone number.
 */
class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create([
            'phone' => '966500000009',
            'verification_code' => 4321,
        ]);
    }

    public function test_deletion_is_rejected_without_a_code(): void
    {
        $user = $this->makeUser();

        $this->post('/delete', [
            'country_prefix' => 'sa',
            'phone' => '966500000009',
            'reason' => 'leaving',
        ])->assertSessionHasErrors('verification_code');

        $this->assertNotSoftDeleted($user);
    }

    public function test_deletion_is_rejected_with_a_wrong_code(): void
    {
        $user = $this->makeUser();

        $this->post('/delete', [
            'country_prefix' => 'sa',
            'phone' => '966500000009',
            'verification_code' => 9999,
            'reason' => 'leaving',
        ])->assertStatus(403);

        $this->assertNotSoftDeleted($user);
    }

    public function test_deletion_succeeds_with_the_correct_code(): void
    {
        $user = $this->makeUser();

        $this->post('/delete', [
            'country_prefix' => 'sa',
            'phone' => '966500000009',
            'verification_code' => 4321,
            'reason' => 'leaving',
        ]);

        $this->assertSoftDeleted($user);
    }
}
