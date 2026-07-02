<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderPricingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * [H1] This controller did NOT exist in the codebase — the reported `/api/invoice/download`
 * endpoint was never implemented (only a broken route referencing a missing class, since removed).
 * Built here securely: an invoice can only be downloaded by the order's own client or artist, and
 * it deliberately omits bank details (IBAN) so it can't leak the other party's private data.
 */
class InvoiceController extends Controller
{
    public function __construct(private readonly OrderPricingService $pricing)
    {
    }

    public function download(Request $request)
    {
        $request->validate(['order_id' => 'required|exists:orders,id']);

        /** @var Order $order */
        $order = Order::with(['client', 'artist', 'categories.subcategory', 'dates', 'address.city'])
            ->findOrFail($request->order_id);

        // [SECURITY] Only a participant of this order may download its invoice — prevents the
        // PII/IBAN enumeration described in H1 (iterating order_id to read others' invoices).
        abort_unless(
            in_array((int) auth()->id(), [(int) $order->client_id, (int) $order->artist_id], true),
            403
        );

        $breakdown = $this->pricing->breakdown((float) $order->total_cost, (float) ($order->coupon_amount ?? 0));

        return Pdf::loadView('invoices.order', compact('order', 'breakdown'))
            ->download('invoice-' . ($order->number ?? $order->id) . '.pdf');
    }
}
