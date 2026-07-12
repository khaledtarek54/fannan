<?php

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Filament\Resources\CouponResource\Pages\CreateCoupon;
use App\Models\Coupon;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Coupon form validation in the admin panel: bound the discount, keep codes unique, and keep the
 * validity window sane. Admin-panel only; no API impact.
 */
class CouponValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'type' => CouponType::FIXED->value,
            'amount' => 10,
            'code' => 'SAVE10',
            'start_date' => now()->toDateTimeString(),
            'end_date' => now()->addWeek()->toDateTimeString(),
        ], $overrides);
    }

    public function test_a_valid_coupon_is_created(): void
    {
        Livewire::test(CreateCoupon::class)
            ->fillForm($this->data())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('coupons', ['code' => 'SAVE10']);
    }

    public function test_a_percentage_coupon_cannot_exceed_100(): void
    {
        Livewire::test(CreateCoupon::class)
            ->fillForm($this->data(['type' => CouponType::PERCENTAGE->value, 'amount' => 150]))
            ->call('create')
            ->assertHasFormErrors(['amount']);
    }

    public function test_a_negative_amount_is_rejected(): void
    {
        Livewire::test(CreateCoupon::class)
            ->fillForm($this->data(['amount' => -5]))
            ->call('create')
            ->assertHasFormErrors(['amount']);
    }

    public function test_a_duplicate_code_is_rejected(): void
    {
        Coupon::create($this->data());

        Livewire::test(CreateCoupon::class)
            ->fillForm($this->data(['code' => 'SAVE10']))
            ->call('create')
            ->assertHasFormErrors(['code']);
    }

    public function test_the_end_date_must_be_after_the_start_date(): void
    {
        Livewire::test(CreateCoupon::class)
            ->fillForm($this->data([
                'start_date' => now()->toDateTimeString(),
                'end_date' => now()->subDay()->toDateTimeString(),
            ]))
            ->call('create')
            ->assertHasFormErrors(['end_date']);
    }
}
