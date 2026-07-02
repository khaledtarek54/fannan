<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guards for CODE_REVIEW_FINDINGS.md B1 — the withdrawal balance check must actually
 * work (it used to read a mistyped key and always allow), including stacking of pending requests.
 */
class WalletWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private function artistWithIncome(int $amount): User
    {
        $artist = User::factory()->artist()->create();
        Transaction::factory()->income()->create(['user_id' => $artist->id, 'amount' => $amount]);

        return $artist;
    }

    public function test_withdrawal_is_rejected_above_balance(): void
    {
        $artist = $this->artistWithIncome(100);

        $this->actingAs($artist, 'api')
            ->postJson('/api/transactions/request', ['amount' => 500])
            ->assertStatus(400);

        $this->assertDatabaseMissing('transactions', ['user_id' => $artist->id, 'type' => 'withdraw']);
    }

    public function test_withdrawal_is_allowed_within_balance(): void
    {
        $artist = $this->artistWithIncome(100);

        $this->actingAs($artist, 'api')
            ->postJson('/api/transactions/request', ['amount' => 50])
            ->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $artist->id, 'type' => 'withdraw', 'amount' => 50,
        ]);
    }

    public function test_pending_withdrawals_cannot_be_stacked_past_the_balance(): void
    {
        $artist = $this->artistWithIncome(100);

        // First request of 60 is allowed (balance 100).
        $this->actingAs(User::find($artist->id), 'api')
            ->postJson('/api/transactions/request', ['amount' => 60])
            ->assertStatus(200);

        // Second request of 60 must be rejected: 60 already committed, only 40 left.
        $this->actingAs(User::find($artist->id), 'api')
            ->postJson('/api/transactions/request', ['amount' => 60])
            ->assertStatus(400);

        $this->assertEquals(1, Transaction::where('user_id', $artist->id)->where('type', 'withdraw')->count());
    }
}
