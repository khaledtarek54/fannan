<?php

namespace App\Services;

use App\Enums\SettingKey;
use App\Models\Setting;

/**
 * Single source of truth for order pricing.
 *
 * Previously the quote (OrderService::checkout) and the actual charge
 * (PaymentService::checkout) computed the total differently — the quote added VAT and
 * the charge did not — so customers were shown one amount and charged another
 * (see docs/CODE_REVIEW_FINDINGS.md B4). Both now call this one method, so they can
 * never diverge.
 *
 * Formula: total = cost + tax% (of cost) − discount + vat% (of the post-tax, post-discount subtotal).
 * Tax and VAT rates come from the `settings` table (`->value`).
 *
 * NOTE (business/legal): this INCLUDES VAT in the charge, matching the quote and KSA VAT
 * rules. If VAT should not be charged, remove the vat term here — it changes both paths at once.
 */
class OrderPricingService
{
    public function breakdown(float $cost, float $discount = 0): array
    {
        $taxRate = $this->rate(SettingKey::TAX->value);
        $vatRate = $this->rate(SettingKey::VAT->value);

        $taxAmount = $cost * $taxRate / 100;
        // [SECURITY][R2-M3] Clamp the discount so a fixed coupon larger than the order can't drive
        // the charge negative, and floor the total at 0.
        $discount = max(0.0, min($discount, $cost + $taxAmount));
        $subtotal = $cost + $taxAmount - $discount;
        $vatAmount = $subtotal * $vatRate / 100;
        $total = max(0.0, $subtotal + $vatAmount);

        return [
            'cost' => $cost,
            'tax' => $taxAmount,
            'vat' => $vatAmount,
            'discount' => $discount,
            'total_cost' => $total,
        ];
    }

    private function rate(string $settingType): float
    {
        return (float) (Setting::query()->where('type', $settingType)->first()?->value ?? 0);
    }
}
