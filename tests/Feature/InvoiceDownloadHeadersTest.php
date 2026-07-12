<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the mobile invoice-download fix.
 *
 * The endpoint used to return a StreamedResponse with NO Content-Length (chunked transfer), which
 * renders fine server-side (HTTP 200, valid PDF) but silently fails on mobile download clients that
 * require Content-Length to save the file. The response must now be a plain PDF response carrying an
 * explicit Content-Length equal to the body size.
 */
class InvoiceDownloadHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_download_returns_pdf_with_explicit_content_length(): void
    {
        $order = Order::factory()->create();

        $response = $this->actingAs($order->client, 'api')
            ->postJson('/api/invoice/download', ['order_id' => $order->id]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // A plain (non-streamed) response now — streamedContent() would throw, which is the fix.
        $body = $response->getContent();

        // The whole point of the fix: a concrete Content-Length matching the payload.
        $this->assertNotNull($response->headers->get('Content-Length'), 'Content-Length must be present');
        $this->assertSame(strlen($body), (int) $response->headers->get('Content-Length'));
        $this->assertStringStartsWith('%PDF', $body, 'body should be a real PDF');
    }

    public function test_a_non_participant_cannot_download_the_invoice(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->client()->create(), 'api')
            ->postJson('/api/invoice/download', ['order_id' => $order->id])
            ->assertStatus(403);
    }
}
