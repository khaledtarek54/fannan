<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Filament\Resources\WithdrawTransactionResource;
use App\Filament\Resources\WithdrawTransactionResource\Pages\CreateWithdrawTransaction;
use App\Filament\Resources\WithdrawTransactionResource\Pages\ListWithdrawTransactions;
use App\Models\Transaction;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Artist payouts (withdrawals) in the admin panel: the balance check must be race-safe, payouts must
 * be immutable, and settling one must re-confirm the admin's password. Admin-panel only; the balance
 * formula matches the API's own, so no divergence is introduced.
 */
class WithdrawalPayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_withdrawal_exceeding_balance_is_rejected_and_creates_no_payout(): void
    {
        $artist = User::factory()->artist()->create();
        Transaction::factory()->income()->create(['user_id' => $artist->id, 'amount' => 100]);

        try {
            (new CreateWithdrawTransaction)->handleRecordCreation([
                'user_id' => $artist->id,
                'amount' => 150, // exceeds the 100 balance
                'type' => TransactionType::WITHDRAW->value,
                'is_completed' => true,
            ]);
            $this->fail('an over-balance withdrawal should have been rejected');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        $this->assertSame(0, Transaction::where('type', TransactionType::WITHDRAW->value)->count());
    }

    public function test_withdrawal_within_balance_creates_the_payout(): void
    {
        $artist = User::factory()->artist()->create();
        Transaction::factory()->income()->create(['user_id' => $artist->id, 'amount' => 100]);

        $payout = (new CreateWithdrawTransaction)->handleRecordCreation([
            'user_id' => $artist->id,
            'amount' => 60,
            'type' => TransactionType::WITHDRAW->value,
            'is_completed' => true,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $payout->id,
            'user_id' => $artist->id,
            'type' => TransactionType::WITHDRAW->value,
            'amount' => 60,
        ]);
    }

    public function test_a_second_withdrawal_that_would_overdraw_is_blocked(): void
    {
        // Once 60 of a 100 balance is withdrawn, a further 60 must fail — withdrawals count against
        // the balance (guards the race/accumulation bug).
        $artist = User::factory()->artist()->create();
        Transaction::factory()->income()->create(['user_id' => $artist->id, 'amount' => 100]);
        Transaction::factory()->withdraw()->create(['user_id' => $artist->id, 'amount' => 60]);

        $this->expectException(ValidationException::class);
        (new CreateWithdrawTransaction)->handleRecordCreation([
            'user_id' => $artist->id,
            'amount' => 60,
            'type' => TransactionType::WITHDRAW->value,
            'is_completed' => true,
        ]);
    }

    public function test_withdrawals_are_immutable_no_edit_route(): void
    {
        $this->assertArrayNotHasKey('edit', WithdrawTransactionResource::getPages());
    }

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
