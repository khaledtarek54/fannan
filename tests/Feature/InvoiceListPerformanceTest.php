<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin invoice list is the heaviest finance page. Its total_cost accessor reads
 * $this->offers->last() per row for direct orders, so the query must eager-load offers to avoid
 * an N+1 across the whole (unbounded) list. Admin-panel only; no API impact.
 */
class InvoiceListPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_invoice_list_eager_loads_offers_client_and_artist(): void
    {
        $eager = InvoiceResource::getEloquentQuery()->getEagerLoads();

        $this->assertArrayHasKey('offers', $eager, 'invoice list must eager-load offers to avoid the total_cost N+1');
        $this->assertArrayHasKey('client', $eager);
        $this->assertArrayHasKey('artist', $eager);
    }
}
