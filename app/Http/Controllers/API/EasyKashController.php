<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePaymentRequest;
use App\Models\Order;
use App\Models\UserTransaction;
use App\Services\EasyKashService;
use App\Services\UserTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EasyKashController extends Controller
{
    protected UserTransactionService $transactionService;
    protected EasyKashService $easykash;

    public function __construct(
        UserTransactionService $transactionService,
        EasyKashService $easykash
    ) {
        $this->transactionService = $transactionService;
        $this->easykash = $easykash;
    }

    public function createPayment(CreatePaymentRequest $request)
    {
        $validated = $request->validated();

        if (isset($validated['order_id'])) {
            $order = Order::find($validated['order_id']);
            if ($order && $order->is_paid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already paid'
                ], 400);
            }
        }

        // Save to DB using resource response
        // Save to DB using Service
        $transaction = $this->transactionService->create($validated);
        // Prepare API payload for EasyKash
        $data = [
            "amount"            => $transaction->amount,
            "name"              => $transaction->name,
            "email"             => $transaction->email,
            "mobile"            => $transaction->mobile,
            "customerReference" => $transaction->customer_reference,
        ];

        $response = $this->easykash->createDirectPayLink($data);

        return response()->json($response);
    }


    public function callback(Request $request, EasyKashService $easyKashService)
    {
        Log::info('=== EasyKash Callback Received ===', [
            'method' => $request->getMethod(),
            'url' => $request->getPathInfo(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            if ($request->isMethod('get')) {
                Log::info('EasyKash GET Redirect Callback', [
                    'query_params' => $request->query(),
                ]);

                $status = strtoupper((string) $request->query('status', ''));
                $customerReference = $request->query('customerReference', '');
                $easykashRef = $request->query('easykashRef') ?? $request->query('providerRefNum');

                Log::info('EasyKash GET params extracted', [
                    'status' => $status,
                    'customerReference' => $customerReference,
                    'easykashRef' => $easykashRef,
                ]);

                // Update the database record before redirecting
                if ($customerReference) {
                    Log::info('Updating transaction from GET redirect', [
                        'customerReference' => $customerReference,
                        'status' => $status,
                    ]);

                    $this->transactionService->updateFromRedirect(
                        $customerReference,
                        $status,
                        $easykashRef
                    );

                    Log::info('Transaction updated successfully from GET redirect', [
                        'customerReference' => $customerReference,
                    ]);
                } else {
                    Log::warning('GET callback received but customerReference is empty');
                }

                return $status === 'PAID'
                    ? redirect('/payment-success.html')
                    : redirect('/payment-failed.html');
            }

            Log::info('EasyKash POST Webhook Callback');

            // Extract payload
            $payloadArray = $request->json()->all();
            if (empty($payloadArray)) {
                Log::info('No JSON payload, trying form data');
                $payloadArray = $request->all();
                if (empty($payloadArray)) {
                    Log::info('No form data, trying raw content');
                    $raw = $request->getContent();
                    if ($raw) {
                        Log::info('Raw content received', ['raw_length' => strlen($raw)]);
                        $decoded = json_decode($raw, true);
                        if (is_array($decoded)) {
                            $payloadArray = $decoded;
                            Log::info('Successfully decoded JSON from raw content');
                        } else {
                            Log::warning('Failed to decode raw content as JSON');
                        }
                    }
                }
            }

            Log::info('EasyKash POST payload received', [
                'customerReference' => $payloadArray['customerReference'] ?? 'N/A',
                'status' => $payloadArray['status'] ?? 'N/A',
                'payload_keys' => array_keys($payloadArray),
            ]);

            $payload = (object) $payloadArray;

            // Signature verification
            Log::info('Verifying EasyKash signature');
            if (! $easyKashService->verifyCallbackSignature($payload)) {
                Log::error('EasyKash signature verification failed', [
                    'customerReference' => $payload->customerReference ?? 'N/A',
                    'received_signature' => $payload->signatureHash ?? 'N/A',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid callback signature'
                ], 401);
            }

            Log::info('EasyKash signature verified successfully', [
                'customerReference' => $payload->customerReference ?? 'N/A',
            ]);

            // Update transaction
            Log::info('Updating transaction from POST callback', [
                'customerReference' => $payload->customerReference ?? 'N/A',
                'status' => $payload->status ?? 'N/A',
            ]);

            $updatedPayment = $this->transactionService
                ->updateFromCallback($payload, $payloadArray);

            if (! $updatedPayment) {
                Log::error('Transaction not found for update', [
                    'customerReference' => $payload->customerReference ?? 'N/A',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found'
                ], 404);
            }

            Log::info('Transaction updated successfully', [
                'customerReference' => $payload->customerReference ?? 'N/A',
                'status' => $payload->status ?? 'N/A',
                'is_paid' => $updatedPayment->is_paid,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your payment is being processed successfully',
            ]);
        } catch (\Throwable $e) {

            // CRITICAL: never expose internal error details
            Log::error('EasyKash callback failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => app()->environment('local') ? $e->getTraceAsString() : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process callback at this time'
            ], 500);
        }
    }

    public function status(\Illuminate\Http\Request $request)
    {
        Log::info('=== EasyKash Status Check ===', [
            'customerReference' => $request->customer_reference ?? 'N/A',
            'timestamp' => now()->toDateTimeString(),
        ]);

        $customerReference = $request->customer_reference ?? $request->checkout_id;

        if (!$customerReference) {
            Log::warning('EasyKash status check - missing customer reference');
            return response()->json([
                'success' => false,
                'message' => 'customer_reference or checkout_id is required'
            ], 400);
        }

        $transaction = $this->transactionService->getTransactionStatus($customerReference);

        if (!$transaction) {
            Log::error('EasyKash transaction not found', [
                'customerReference' => $customerReference,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
                'status' => 'NOT_FOUND',
            ], 404);
        }

        Log::info('EasyKash transaction status retrieved', [
            'customerReference' => $customerReference,
            'status' => $transaction->status,
            'is_paid' => $transaction->is_paid,
        ]);

        return response()->json([
            'success' => $transaction->is_paid,
            'status' => $transaction->status,
            'is_paid' => (bool) $transaction->is_paid,
            'amount' => $transaction->amount,
            'amount_paid' => $transaction->amount_paid,
            'customerReference' => $transaction->customer_reference,
            'easykashRef' => $transaction->easykash_ref,
            'payment_method' => $transaction->payment_method,
            'product_type' => $transaction->product_type,
            'message' => $transaction->is_paid ? 'Payment successful' : 'Payment ' . strtolower($transaction->status),
        ]);
    }
}
