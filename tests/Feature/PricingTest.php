<?php

namespace Tests\Feature;

use App\Enums\SettingKey;
use App\Models\Setting;
use App\Services\OrderPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for CODE_REVIEW_FINDINGS.md B4 — the quote (OrderService) and the charge
 * (PaymentService) both use OrderPricingService, so they can never diverge. These tests pin the
 * formula: total = cost + tax% − discount + vat% (VAT on the post-tax, post-discount subtotal).
 */
class PricingTest extends TestCase
{
    use RefreshDatabase;

    private function seedRates(int $tax, int $vat): void
    {
        Setting::create(['type' => SettingKey::TAX->value, 'value' => $tax]);
        Setting::create(['type' => SettingKey::VAT->value, 'value' => $vat]);
    }

    public function test_tax_then_vat_are_applied_consistently(): void
    {
        $this->seedRates(10, 14);

        $b = app(OrderPricingService::class)->breakdown(100.0, 0.0);

        $this->assertEqualsWithDelta(10.0, $b['tax'], 0.001);        // 10% of 100
        $this->assertEqualsWithDelta(15.4, $b['vat'], 0.001);        // 14% of 110
        $this->assertEqualsWithDelta(125.4, $b['total_cost'], 0.001);
    }

    public function test_discount_is_applied_before_vat(): void
    {
        $this->seedRates(10, 14);

        // tax 10 -> subtotal 100+10-20 = 90 -> vat 12.6 -> total 102.6
        $b = app(OrderPricingService::class)->breakdown(100.0, 20.0);

        $this->assertEqualsWithDelta(102.6, $b['total_cost'], 0.001);
    }

    public function test_missing_settings_default_to_zero_rate(): void
    {
        $b = app(OrderPricingService::class)->breakdown(100.0, 0.0);

        $this->assertEqualsWithDelta(100.0, $b['total_cost'], 0.001);
    }
}
