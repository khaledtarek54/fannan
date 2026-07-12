<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    /**
     * Download invoice as PDF.
     */
    public function download(Request $request)
    {
        Log::info('=== Invoice Download Request ===', [
            'order_id' => $request->order_id,
            'timestamp' => now()->toDateTimeString(),
        ]);

        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        try {
            $order = Order::with(InvoiceService::INVOICE_RELATIONS)->findOrFail($request->order_id);

            // [SECURITY] Only the order's own client or artist may download its invoice (H1 IDOR).
            $userId = (int) auth()->id();
            abort_unless((int) $order->client_id === $userId || (int) $order->artist_id === $userId, 403);

            $invoiceData = $this->invoices->prepareInvoiceData($order);

            Log::info('Invoice data prepared', [
                'order_id' => $order->id,
                'invoice_number' => $invoiceData['invoice_number'],
            ]);

            $pdf = $this->invoices->renderPdf($invoiceData);
            $fileName = $invoiceData['invoice_number'] . '.pdf';

            // [FIX] Return the fully-rendered PDF as a normal response with an explicit
            // Content-Length instead of a chunked StreamedResponse. Mobile download clients need
            // Content-Length to save the file; streamDownload() omits it (chunked transfer), which
            // silently breaks the download even though the server returns 200. The PDF is already
            // in memory, so streaming buys nothing.
            return response($pdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Content-Length'      => (string) strlen($pdf),
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e; // [SECURITY] Preserve 403/404 — don't mask the ownership check as a 500 (H1).
        } catch (\Throwable $e) {
            Log::error('Invoice download failed', [
                'order_id' => $request->order_id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to download invoice at this time',
            ], 500);
        }
    }

    /**
     * LOCAL-ONLY preview so the invoice design can be iterated without auth or the mobile app.
     *
     *   /invoice/preview            -> newest order, or built-in sample data if the DB is empty (HTML)
     *   /invoice/preview/{order}    -> a specific order (HTML)
     *   /invoice/preview?pdf=1      -> the same, rendered as the real PDF
     *
     * Registered only when app()->environment('local') (see routes/web.php).
     */
    public function preview(Request $request, ?int $order = null)
    {
        abort_unless(app()->environment('local'), 404);

        $model = $order
            ? Order::with(InvoiceService::INVOICE_RELATIONS)->find($order)
            : Order::with(InvoiceService::INVOICE_RELATIONS)->latest('id')->first();

        $data = $model ? $this->invoices->prepareInvoiceData($model) : $this->invoices->sampleInvoiceData();

        if ($request->boolean('pdf')) {
            $pdf = $this->invoices->renderPdf($data);

            // ?download=1 forces a file download (attachment); otherwise the PDF opens inline.
            $disposition = $request->boolean('download') ? 'attachment' : 'inline';

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="' . $data['invoice_number'] . '.pdf"',
            ]);
        }

        // HTML preview is far faster to iterate on than re-rendering the PDF each time.
        // $browser = true adds the @font-face, page framing, CSS watermark and footer bar.
        return response($this->invoices->renderHtml($data, true));
    }

    /**
     * Get all orders for authenticated user with pagination.
     */
    public function getAllOrders(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);

        try {
            $orders = Order::where('client_id', auth()->id())
                ->with([
                    'artist',
                    'userTransaction' => fn ($query) => $query->latest()->limit(1),
                ])
                ->paginate($perPage, ['*'], 'page', $page);

            $formattedOrders = collect($orders->items())->map(function ($order) {
                $latestTransaction = $order->userTransaction()->latest()->first();
                $paymentStatus = $this->determinePaymentStatus($order, $latestTransaction);
                $totalPrice = (float) ($order->cost - ($order->coupon_amount ?? 0));

                return [
                    'id' => $order->id,
                    'status' => $paymentStatus['status'],
                    'artist_name' => $order->artist->name ?? 'N/A',
                    'total_price' => $totalPrice,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedOrders,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve all orders', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve orders',
            ], 500);
        }
    }

    /**
     * Get order details with latest transaction status.
     */
    public function getOrderStatus(Request $request)
    {
        $orderId = $request->query('order_id') ?? $request->input('order_id');

        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ], [
            'order_id.required' => 'Order ID is required as a query parameter',
            'order_id.exists' => 'Order not found',
        ]);

        try {
            $order = Order::with([
                'artist',
                'userTransaction' => fn ($query) => $query->latest()->limit(1),
            ])->findOrFail($orderId);

            // [SECURITY] Only a participant (client or artist) of the order may read its status (M2 IDOR).
            $userId = (int) auth()->id();
            abort_unless((int) $order->client_id === $userId || (int) $order->artist_id === $userId, 403);

            $latestTransaction = $order->userTransaction()->latest()->first();
            $paymentStatus = $this->determinePaymentStatus($order, $latestTransaction);
            $totalPrice = (float) ($order->cost - ($order->coupon_amount ?? 0));

            // [M2] Return the order's LIFECYCLE status wrapped in a `data` envelope; keep the
            // payment status alongside. See docs/SECURITY_ISSUES.md M2.
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $order->latestStatus()?->name,
                    'payment_status' => $paymentStatus['status'],
                    'artist_name' => $order->artist->name ?? 'N/A',
                    'total_price' => $totalPrice,
                ],
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e; // [SECURITY] Preserve 403/404 — don't mask the ownership check as a 500 (M2).
        } catch (\Throwable $e) {
            Log::error('Order status retrieval failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve order status',
            ], 500);
        }
    }

    /**
     * Determine payment status from order and latest transaction.
     */
    private function determinePaymentStatus($order, $transaction): array
    {
        if ($order->is_paid) {
            return ['status' => 'PAID', 'label' => 'Payment Successful'];
        }

        if (!$transaction) {
            return ['status' => 'PENDING', 'label' => 'Awaiting Payment'];
        }

        $transactionStatus = strtoupper($transaction->status ?? 'PENDING');

        return match ($transactionStatus) {
            'PAID' => ['status' => 'PAID', 'label' => 'Payment Successful'],
            'FAILED' => ['status' => 'FAILED', 'label' => 'Payment Failed'],
            'CANCELLED' => ['status' => 'CANCELLED', 'label' => 'Payment Cancelled'],
            'PENDING' => ['status' => 'PENDING', 'label' => 'Payment Pending'],
            default => [
                'status' => strtoupper($transaction->status ?? 'UNKNOWN'),
                'label' => 'Payment ' . ucfirst($transaction->status ?? 'Unknown'),
            ],
        };
    }
}
