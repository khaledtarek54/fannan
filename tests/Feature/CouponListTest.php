<?php

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Filament\Resources\CouponResource;
use App\Filament\Resources\CouponResource\Pages\ListCoupons;
use App\Models\Coupon;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The coupon list's computed columns (validity status + amount unit). Admin-panel only; no API impact.
 */
class CouponListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function coupon(string $code, $start, $end, string $type = CouponType::FIXED->value, $amount = 10): Coupon
    {
        return Coupon::create([
            'type' => $type,
            'amount' => $amount,
            'code' => $code,
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    public function test_validity_status_reflects_the_window(): void
    {
        $scheduled = $this->coupon('FUTURE', now()->addWeek(), now()->addMonth());
        $active = $this->coupon('NOW', now()->subDay(), now()->addDay());
        $expired = $this->coupon('OLD', now()->subMonth(), now()->subWeek());

        $this->assertSame('scheduled', CouponResource::validityStatus($scheduled));
        $this->assertSame('active', CouponResource::validityStatus($active));
        $this->assertSame('expired', CouponResource::validityStatus($expired));
    }

    public function test_amount_column_shows_percent_for_percentage_and_currency_for_fixed(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $percentage = $this->coupon('PCT', now(), now()->addWeek(), CouponType::PERCENTAGE->value, 20);
        $fixed = $this->coupon('FIX', now(), now()->addWeek(), CouponType::FIXED->value, 20);

        Livewire::test(ListCoupons::class)
            ->assertTableColumnFormattedStateSet('amount', '20%', $percentage)
            ->assertTableColumnFormattedStateSet('amount', money(20.0), $fixed);
    }
}
