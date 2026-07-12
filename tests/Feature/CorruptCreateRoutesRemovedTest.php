<?php

namespace Tests\Feature;

use App\Filament\Resources\BiddingOrderResource;
use App\Filament\Resources\SettingResource;
use Tests\TestCase;

/**
 * The admin "create" routes for bidding orders and settings produced corrupt rows (wrong type /
 * NULL-type, the latter breaking the mobile /settings response), so they were removed. Admin-panel
 * only; no API impact.
 */
class CorruptCreateRoutesRemovedTest extends TestCase
{
    public function test_bidding_order_create_route_is_removed(): void
    {
        $this->assertArrayNotHasKey('create', BiddingOrderResource::getPages(), 'bidding-order create produced corrupt type=direct rows');
    }

    public function test_setting_create_route_is_removed(): void
    {
        $this->assertArrayNotHasKey('create', SettingResource::getPages(), 'setting create wrote a NULL-type row that broke the app settings response');
    }
}
