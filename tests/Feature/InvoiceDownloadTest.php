<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard for SECURITY_ISSUES.md H1 — the invoice-download endpoint did not exist and was built here
 * securely: only the order's client or artist may download it (no PII/IBAN enumeration by order_id).
 */
class InvoiceDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_participant_can_download_the_invoice(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($order->client, 'api')
            ->get('/api/invoice/download?order_id=' . $order->id)
            ->assertStatus(200)
            ->assertSee('Fannan');
    }

    public function test_a_non_participant_cannot_download_the_invoice(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->client()->create(), 'api')
            ->get('/api/invoice/download?order_id=' . $order->id)
            ->assertStatus(403);
    }
}
