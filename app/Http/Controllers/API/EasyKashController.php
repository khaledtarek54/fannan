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
            // [SECURITY] Only the order's client may create a payment for it (M6).
            abort_unless($order && (int) $order->client_id === (int) auth()->id(), 403);
            if ($order->is_paid) {
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

        try {
            if ($request->isMethod('get')) {
                // [SECURITY] The GET redirect is the shopper's browser returning from
                // EasyKash and is NOT authenticated or signed. It must never mutate
                // payment state — otherwise anyone can mark an order PAID via a crafted
                // URL (see docs/SECURITY_ISSUES.md C2). Payment state is only changed by
                // the signature-verified POST webhook below. Here we redirect based on the
                // record's ACTUAL stored state, not the untrusted query string.
                $customerReference = $request->query('customerReference', '');
                $payment = $customerReference
                    ? UserTransaction::where('customer_reference', $customerReference)->first()
                    : null;

                return ($payment && $payment->is_paid)
                    ? redirect('/payment-success.html')
                    : redirect('/payment-failed.html');
            }
            $payloadArray = $request->json()->all();
            if (empty($payloadArray)) {
                $payloadArray = $request->all();
                if (empty($payloadArray)) {
                    $raw = $request->getContent();
                    if ($raw) {
                        $decoded = json_decode($raw, true);
                        if (is_array($decoded)) {
                            $payloadArray = $decoded;
                        }
                    }
                }
            }
            $payload = (object) $payloadArray;
            // Signature verification
            if (! $easyKashService->verifyCallbackSignature($payload)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid callback signature'
                ], 401);
            }

            // Update transaction
            $updatedPayment = $this->transactionService
                ->updateFromCallback($payload, $payloadArray);

            if (! $updatedPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your payment is being processed successfully',
            ]);
        } catch (\Throwable $e) {

            // CRITICAL: never expose internal error details
            Log::error('EasyKash callback failed', [
                'error' => $e->getMessage(),
                'trace' => app()->environment('local') ? $e->getTraceAsString() : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process callback at this time'
            ], 500);
        }
    }
}
