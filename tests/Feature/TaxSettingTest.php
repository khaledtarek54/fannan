<?php

namespace Tests\Feature;

use App\Enums\SettingKey;
use App\Filament\Resources\TaxResource;
use App\Models\Setting;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Tax/rates settings screen must expose the tax rate that OrderPricingService actually charges.
 * Admin-panel only; no API impact.
 */
class TaxSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_tax_screen_exposes_the_real_tax_setting_that_orders_are_charged(): void
    {
        Setting::create(['type' => SettingKey::TAX->value, 'value' => ['en' => '5', 'ar' => '5']]);

        $types = TaxResource::getEloquentQuery()->pluck('type');

        $this->assertTrue($types->contains(SettingKey::TAX->value), 'the tax rate must be editable in the Tax screen');
    }
}
