<?php

namespace Tests\Feature;

use App\Enums\SettingKey;
use App\Enums\TransactionType;
use App\Filament\Resources\BiddingOrderResource;
use App\Filament\Resources\SettingResource;
use App\Filament\Resources\TaxResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\WithdrawTransactionResource;
use App\Filament\Resources\WithdrawTransactionResource\Pages\CreateWithdrawTransaction;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Dashboard Phase 1 — admin-login criticals, withdrawal money-integrity, and the data-corruption
 * fixes. All admin-panel only: none of these touch the mobile API (guarded by the rest of the suite).
 */
class DashboardPhase1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    // ---- Admin login criticals -------------------------------------------------

    public function test_admins_screen_lists_by_is_admin_not_the_role_column(): void
    {
        $realAdmin = User::factory()->admin()->create();
        // role='admin' is the DB default, so a plain user can carry it while is_admin stays false.
        $fakeAdmin = User::factory()->create();
        $fakeAdmin->forceFill(['role' => 'admin', 'is_admin' => false])->save();

        $ids = UserResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($ids->contains($realAdmin->id), 'the real is_admin account must be listed');
        $this->assertFalse($ids->contains($fakeAdmin->id), 'a role=admin/is_admin=false row must NOT be listed');
    }

    public function test_creating_an_admin_sets_is_admin_so_the_account_can_log_in(): void
    {
        $user = (new CreateUser)->handleRecordCreation([
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'phone' => '+201000000001',
            'password' => 'secret123',
            'country_code' => 'EG',
        ]);

        $this->assertTrue($user->fresh()->is_admin, 'panel-created admin must have is_admin=true');
        $this->assertTrue($user->fresh()->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_an_admin_cannot_delete_the_last_remaining_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callTableAction('delete', $admin);

        // Self + last-admin guard: the account must survive (not soft-deleted).
        $this->assertNull($admin->fresh()->deleted_at, 'the last/self admin must not be deletable');
    }

    public function test_an_admin_can_delete_another_admin_when_not_the_last(): void
    {
        $me = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $this->actingAs($me);

        Livewire::test(ListUsers::class)
            ->callTableAction('delete', $other);

        $this->assertNotNull($other->fresh()->deleted_at, 'a non-last, non-self admin should be deletable');
    }

    // ---- Withdrawal money integrity --------------------------------------------

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
        // Guards against the race/accumulation bug: once 60 of a 100 balance is withdrawn,
        // a further 60 must fail because withdrawals count against the balance.
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

    // ---- Tax rate editability --------------------------------------------------

    public function test_tax_screen_exposes_the_real_tax_setting_that_orders_are_charged(): void
    {
        $tax = Setting::create(['type' => SettingKey::TAX->value, 'value' => ['en' => '5', 'ar' => '5']]);

        $types = TaxResource::getEloquentQuery()->pluck('type');

        $this->assertTrue($types->contains(SettingKey::TAX->value), 'the tax rate must be editable in the Tax screen');
    }

    // ---- Corrupt create routes removed -----------------------------------------

    public function test_corrupt_create_routes_are_removed(): void
    {
        $this->assertArrayNotHasKey('create', BiddingOrderResource::getPages(), 'bidding-order create produced corrupt rows');
        $this->assertArrayNotHasKey('create', SettingResource::getPages(), 'setting create produced app-breaking NULL-type rows');
    }
}
