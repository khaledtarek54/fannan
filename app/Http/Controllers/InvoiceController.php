<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Download invoice as PDF
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
            $order = Order::with([
                'client',
                'artist',
                'address',
                'address.city',
                'categories.subcategory',
                'dates',
                'userTransaction',
            ])->findOrFail($request->order_id);

            Log::info('Order retrieved successfully', [
                'order_id' => $order->id,
                'client_name' => $order->client->name ?? 'N/A',
                'artist_name' => $order->artist->name ?? 'N/A',
            ]);

            // Prepare invoice data
            $invoiceData = $this->prepareInvoiceData($order);

            Log::info('Invoice data prepared', [
                'order_id' => $order->id,
                'invoice_number' => $invoiceData['invoice_number'],
            ]);

            // Generate PDF
            $html = View::make('invoices.template', $invoiceData)->render();
            
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $fileName = 'Invoice-' . $invoiceData['invoice_number'] . '.pdf';

            Log::info('Invoice PDF generated', [
                'order_id' => $order->id,
                'file_name' => $fileName,
            ]);

            return response()->streamDownload(
                function () use ($dompdf) {
                    echo $dompdf->output();
                },
                $fileName,
                ['Content-Type' => 'application/pdf']
            );
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
     * Get all orders for authenticated user with pagination
     */
    public function getAllOrders(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        
        Log::info('=== Get All Orders Request ===', [
            'user_id' => auth()->id(),
            'per_page' => $perPage,
            'page' => $page,
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            // Get all orders for the authenticated user (as client)
            $orders = Order::where('client_id', auth()->id())
                ->with([
                    'artist',
                    'userTransaction' => function ($query) {
                        $query->latest()->limit(1);
                    },
                ])
                ->paginate($perPage, ['*'], 'page', $page);

            // Transform the data
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

            Log::info('All orders retrieved', [
                'user_id' => auth()->id(),
                'total_orders' => $orders->total(),
                'current_page' => $page,
            ]);

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
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve orders',
            ], 500);
        }
    }

    /**
     * Get order details with latest transaction status
     */
    public function getOrderStatus(Request $request)
    {
        $orderId = $request->query('order_id') ?? $request->input('order_id');
        
        Log::info('=== Order Status Request ===', [
            'order_id' => $orderId,
            'timestamp' => now()->toDateTimeString(),
        ]);

        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ], [
            'order_id.required' => 'Order ID is required as a query parameter',
            'order_id.exists' => 'Order not found',
        ]);

        try {
            $order = Order::with([
                'artist',
                'userTransaction' => function ($query) {
                    $query->latest()->limit(1);
                },
            ])->findOrFail($orderId);

            Log::info('Order retrieved successfully for status', [
                'order_id' => $order->id,
                'is_paid' => $order->is_paid,
            ]);

            // Get the latest transaction
            $latestTransaction = $order->userTransaction()->latest()->first();

            // Determine payment status
            $paymentStatus = $this->determinePaymentStatus($order, $latestTransaction);

            // Calculate total price
            $totalPrice = (float) ($order->cost - ($order->coupon_amount ?? 0));

            $orderStatus = [
                'success' => true,
                'status' => $paymentStatus['status'],
                'artist_name' => $order->artist->name ?? 'N/A',
                'total_price' => $totalPrice,
            ];

            Log::info('Order status prepared', [
                'order_id' => $order->id,
                'payment_status' => $paymentStatus['status'],
                'total_price' => $totalPrice,
            ]);

            return response()->json($orderStatus);
        } catch (\Throwable $e) {
            Log::error('Order status retrieval failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve order status',
            ], 500);
        }
    }

    /**
     * Determine payment status from order and latest transaction
     */
    private function determinePaymentStatus($order, $transaction): array
    {
        // If order is marked as paid
        if ($order->is_paid) {
            return [
                'status' => 'PAID',
                'label' => 'Payment Successful',
            ];
        }

        // If no transaction exists, order is pending
        if (!$transaction) {
            return [
                'status' => 'PENDING',
                'label' => 'Awaiting Payment',
            ];
        }

        // Check transaction status
        $transactionStatus = strtoupper($transaction->status ?? 'PENDING');

        return match ($transactionStatus) {
            'PAID' => [
                'status' => 'PAID',
                'label' => 'Payment Successful',
            ],
            'FAILED' => [
                'status' => 'FAILED',
                'label' => 'Payment Failed',
            ],
            'CANCELLED' => [
                'status' => 'CANCELLED',
                'label' => 'Payment Cancelled',
            ],
            'PENDING' => [
                'status' => 'PENDING',
                'label' => 'Payment Pending',
            ],
            default => [
                'status' => strtoupper($transaction->status ?? 'UNKNOWN'),
                'label' => 'Payment ' . ucfirst($transaction->status ?? 'Unknown'),
            ],
        };
    }

    /**
     * Prepare all necessary data for invoice template
     */
    private function prepareInvoiceData(Order $order): array
    {
        // Get primary order date
        $primaryDate = $order->dates()->first();

        // Get primary category
        $primaryCategory = $order->categories()->with('subcategory')->first();

        // Get transaction details
        $transaction = $order->userTransaction()->latest()->first();

        // Calculate totals
        $serviceAmount = $order->cost ?? 0;
        $discount = $order->coupon_amount ?? 0;
        $taxPercentage = 0.15; // 15% tax
        $vatPercentage = 0; // Can be adjusted based on requirements

        $amountAfterDiscount = $serviceAmount - $discount;
        $tax = $amountAfterDiscount * $taxPercentage;
        $vat = ($amountAfterDiscount + $tax) * $vatPercentage;
        $totalAmount = $amountAfterDiscount + $tax + $vat;

        // Company details (hardcoded or from settings)
        $companyDetails = [
            'name' => 'Fannan',
            'address' => 'P.O. Box 91 Street 5, Musk, Cairo, 11411',
            'phone' => 'FAX: +20220098882',
            'email' => 'info@fannanonline.com',
            'taxId' => 'TAX #201050789844832',
            'website' => 'www.fannanonline.com',
        ];

        // Bank details
        $bankDetails = [
            'bank_name' => 'Bank / Company Details:',
            'iban' => $order->artist->iban ?? 'N/A',
            'swift' => 'SWIFT Code / Ref Num: [Additional Details]',
        ];

        return [
            'invoice_number' => 'Date-' . $order->id,
            'invoice_date' => now()->format('Y-m-d'),
            'order_id' => $order->id,

            // Company details
            'company' => $companyDetails,
            'bank_details' => $bankDetails,

            // Client details
            'client' => [
                'name' => $order->client->name ?? 'N/A',
                'id' => $order->client->id ?? 'N/A',
                'email' => $order->client->email ?? 'N/A',
                'phone' => $order->client->phone ?? 'N/A',
            ],

            // Order/Service details
            'service' => [
                'artist_name' => $order->artist->name ?? 'N/A',
                'category' => $primaryCategory?->subcategory?->name ?? 'N/A',
                'type' => $order->type ?? 'N/A',
                'description' => $order->description ?? 'N/A',
                'address' => $order->address?->description ?? $order->address?->name ?? 'N/A',
                'address_city' => $order->address?->city?->name ?? 'N/A',
            ],

            // Dates and times
            'dates' => [
                'start_date' => $this->formatDate($primaryDate?->start_date) ?? 'N/A',
                'end_date' => $this->formatDate($primaryDate?->end_date) ?? 'N/A',
                'start_time' => $primaryDate?->start_time ?? 'N/A',
                'end_time' => $primaryDate?->end_time ?? 'N/A',
                'time_period' => $this->calculateTimePeriod($primaryDate),
                'number_of_days' => $this->calculateDays($primaryDate),
                'daily_hours' => $this->calculateDailyHours($primaryDate),
                'total_hours' => $this->calculateTotalHours($primaryDate),
            ],

            // Financial details
            'financial' => [
                'service_cost' => number_format($serviceAmount, 2),
                'discount' => number_format($discount, 2),
                'tax' => number_format($tax, 2),
                'vat' => number_format($vat, 2),
                'total' => number_format($totalAmount, 2),
                'currency' => 'EGP',
            ],

            // Payment details
            'payment' => [
                'status' => $order->is_paid ? 'PAID' : 'PENDING',
                'method' => $transaction?->payment_method ?? 'N/A',
                'reference' => $transaction?->customer_reference ?? 'N/A',
                'easykash_ref' => $transaction?->easykash_ref ?? 'N/A',
                'amount_paid' => number_format($transaction?->amount_paid ?? 0, 2),
            ],
        ];
    }

    /**
     * Format date string or Carbon object to Y-m-d
     */
    private function formatDate($date): ?string
    {
        if (!$date) {
            return null;
        }

        if (is_string($date)) {
            return $date;
        }

        return $date->format('Y-m-d');
    }

    /**
     * Convert to Carbon if string
     */
    private function toCarbon($date): ?\Carbon\Carbon
    {
        if (!$date) {
            return null;
        }

        if (is_string($date)) {
            return \Carbon\Carbon::parse($date);
        }

        return $date;
    }

    /**
     * Calculate time period string
     */
    private function calculateTimePeriod(?OrderDate $date): string
    {
        if (!$date) {
            return 'N/A';
        }

        $startDate = $this->toCarbon($date->start_date);
        $endDate = $this->toCarbon($date->end_date);

        if (!$startDate || !$endDate) {
            return 'N/A';
        }

        try {
            $dayName = $startDate->format('l');
            $month = $startDate->format('F');
            $dayOfMonth = $startDate->format('d');

            // Example: "Thursday November 6 - Sunday November 10 (8 hours)"
            $dayDiff = $endDate->diffInDays($startDate) + 1;

            return $dayName . ' ' . $month . ' ' . $dayOfMonth . ' - ' .
                $endDate->format('l') . ' ' . $endDate->format('F') . ' ' . $endDate->format('d') .
                ' (' . $dayDiff . ' days)';
        } catch (\Exception $e) {
            Log::warning('Failed to calculate time period', ['error' => $e->getMessage()]);
            return 'N/A';
        }
    }

    /**
     * Calculate number of days between dates
     */
    private function calculateDays(?OrderDate $date): int
    {
        if (!$date || !$date->start_date || !$date->end_date) {
            return 0;
        }

        try {
            $startDate = $this->toCarbon($date->start_date);
            $endDate = $this->toCarbon($date->end_date);
            return $endDate->diffInDays($startDate) + 1;
        } catch (\Exception $e) {
            Log::warning('Failed to calculate days', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Calculate daily working hours
     */
    private function calculateDailyHours(?OrderDate $date): string
    {
        if (!$date || !$date->start_time || !$date->end_time) {
            return '0';
        }

        try {
            // Handle both HH:MM and HH:MM:SS formats
            $startTimeStr = $date->start_time;
            $endTimeStr = $date->end_time;
            
            // Add seconds if not present
            if (substr_count($startTimeStr, ':') == 1) {
                $startTimeStr .= ':00';
            }
            if (substr_count($endTimeStr, ':') == 1) {
                $endTimeStr .= ':00';
            }

            $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $startTimeStr);
            $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $endTimeStr);

            // If end time is on next day, add 24 hours
            if ($endTime < $startTime) {
                $endTime->addDay();
            }

            $hours = $endTime->diffInHours($startTime);
            return (string) $hours;
        } catch (\Exception $e) {
            Log::warning('Failed to calculate daily hours', [
                'start_time' => $date->start_time,
                'end_time' => $date->end_time,
                'error' => $e->getMessage(),
            ]);
            return '0';
        }
    }

    /**
     * Calculate total working hours
     */
    private function calculateTotalHours(?OrderDate $date): int
    {
        $dailyHours = (int) $this->calculateDailyHours($date);
        $numberOfDays = $this->calculateDays($date);

        return $dailyHours * $numberOfDays;
    }
}
