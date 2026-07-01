<?php

use App\Http\Controllers\EasyKashController;
use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\ArtistController;
use App\Http\Controllers\API\ArtistGalleryController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BiddingOrderArtistController;
use App\Http\Controllers\API\BiddingOrderController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\CouponController;
use App\Http\Controllers\ApI\EasyKashController as ApiEasyKashController;
use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\RatingController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\SupportController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// [SECURITY] Requires auth and only returns the caller's own transactions (M1).
// Previously public and referenced an undefined model, enabling enumeration of
// all customer references / payment data.
Route::middleware('auth:api')->get('payments/easykash/status', function (Request $req) {
    $ref = $req->query('customerReference');
    if (! $ref) return response()->json(['error' => 'Missing customerReference'], 400);

    $p = \App\Models\UserTransaction::where('customer_reference', $ref)
        ->whereHas('order', fn ($q) => $q->where('client_id', auth()->id()))
        ->first();
    if (! $p) return response()->json(['status' => 'NOT_FOUND'], 404);

    return response()->json([
        'status' => $p->status,
        'easykash_ref' => $p->easykash_ref,
    ]);
});


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/address/reverse-geocode', [AddressController::class, 'reverseGeocode']);

// [SECURITY] Auth endpoints use a dedicated stricter limiter to resist credential
// brute-force and SMS/verification-code abuse. See docs/SECURITY_ISSUES.md M10/M11.
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login-social', [AuthController::class, 'socialLogin']);
    Route::post('social/login', [AuthController::class, 'socialLogin']);
    Route::post('/verification/check', [AuthController::class, 'checkCode']);
    Route::post('send/code', [AuthController::class, 'sendCodeAgain']);
    Route::post('/password/update', [AuthController::class, 'updatePassword']);
});

// [SECURITY] Removed public apiResource('invoices') — it exposed unauthenticated
// CRUD routes wired to a non-existent InvoiceController (see docs/SECURITY_ISSUES.md L1).

Route::get('categories', [CategoryController::class, 'index']);
Route::get('settings', [SettingController::class, 'index']);
Route::get('price-ranges', [SettingController::class, 'priceRanges']);
Route::get('artist-acknowledgement', [SettingController::class, 'artistAcknowledgement']);


Route::post('update/fcm_token', [AuthController::class, 'updateToken'])->middleware("auth:api");
Route::get('cities', [Controller::class, 'cities']);

Route::controller(HomeController::class)->group(function () {
    Route::get('/home', 'index');
});

Route::controller(ArtistController::class)->prefix('artist')->group(function () {
    Route::get('/', 'index');
    Route::post('/id', 'getArtistById');
});

Route::middleware(['auth:api', 'DeleteAccount'])->group(function () {

    Route::get('update-lang', [Controller::class, 'updateLang']);
    Route::controller(ClientController::class)->prefix('client')->group(function () {
        Route::post('complete/profile', 'completeProfile');
        Route::post('update', 'updateProfile');
        Route::get('profile', 'profile');
    });

    Route::get('delete-account', [ClientController::class, 'deleteAccount']);

    Route::controller(ArtistController::class)->prefix('artist')->group(function () {
        //        Route::get('/', 'index');
        //        Route::post('/id', 'getArtistById');
        Route::post('categories/update', 'updateCategories');
        Route::get('profile', 'profile');
    });

    Route::controller(ArtistGalleryController::class)
        ->prefix('gallery')
        ->group(function () {
            Route::get('', 'index');
            Route::post('create', 'create');
            Route::post('delete', 'destroy');
            Route::post('update', 'update');
        });

    Route::controller(SupportController::class)->prefix('support')->group(function () {
        Route::get('/', 'index');
        Route::post('/create', 'store');
        Route::post('/delete', 'destroy');
    });


    Route::controller(HomeController::class)->group(function () {
        //        Route::get('/home', 'index');
        Route::get('/artist/home', 'artistHome');
    });

    Route::controller(OrderController::class)
        ->middleware('CompleteProfileMiddleware')
        ->prefix('order')
        ->as('order.')
        ->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('artist', 'artistOrders')->name('artistOrders');
            Route::post('store', 'store')->name('store');
            Route::post('accept', 'accept')->name('accept');
            Route::post('offer', 'offer')->name('offer');
            Route::post('reject', 'reject')->name('reject');
            Route::post('cancel', 'cancel')->name('cancel');
            Route::post('checkout', 'checkout')->name('checkout');
        });

    Route::controller(CouponController::class)
        ->prefix('coupon')
        ->as('coupon.')
        ->group(function () {
            Route::post('check-coupon', 'checkValidCoupon')->name('checkValidCoupon');
        });
    Route::controller(BiddingOrderController::class)
        ->prefix('bidding-order')
        ->middleware('CompleteProfileMiddleware')
        ->as('bidding-order.')
        ->group(function () {
            Route::get('/', 'index')->name('all');
            Route::post('/id', 'show')->name('show');
            Route::post('store', 'store')->name('store');
            Route::post('send-offer', 'offer')->name('offer');
            Route::get('available', 'available')->name('available');
        });
    Route::controller(BiddingOrderArtistController::class)
        ->prefix('offers')
        ->middleware('CompleteProfileMiddleware')
        ->as('offers.')
        ->group(function () {
            Route::get('/', 'index')->name('offer');
            Route::post('/accept', 'accept')->name('accept');
            Route::post('/reject', 'reject')->name('reject');
        });

    Route::controller(RatingController::class)
        ->prefix('rating')
        ->middleware('CompleteProfileMiddleware')
        ->as('rating.')
        ->group(function () {
            Route::post('/store', 'store')->name('store');
        });

    Route::controller(ChatController::class)
        ->prefix('chat')
        ->as('chat.')
        ->group(function () {
            Route::get('/', 'chats');
            Route::post('/details', 'chat');
            Route::post('store', 'store');
        });

    Route::controller(TransactionController::class)
        ->prefix('transactions')
        ->as('transactions.')
        ->group(function () {
            Route::get('/', 'transactions');
            Route::post('/request', 'request');
        });

    Route::controller(NotificationController::class)->group(function () {
        Route::get('notifications', 'index');
    });

    Route::controller(AddressController::class)
        ->prefix('address')
        ->as('address.')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('store', 'store');
            Route::post('delete', 'destroy');
        });

    Route::controller(PaymentController::class)
        ->prefix('payment')
        ->middleware(['CompleteProfileMiddleware', 'throttle:payment'])
        ->as('payment.')
        ->group(function () {
            Route::post('checkout', 'checkout');
            Route::post('/status', 'checkPaymentStatus');
        });
    Route::post('payment-webhook', [PaymentController::class, 'webhook']);
});
Route::controller(ApiEasyKashController::class)
    ->prefix('easykash')
    ->as('easykash.')
    ->group(function () {
        // [SECURITY] pay now requires auth + payment throttle (M6/M11). callback stays public
        // for the external gateway; its POST branch is HMAC-verified (see EasyKashController).
        Route::post('pay', 'createPayment')->middleware(['auth:api', 'throttle:payment']);
        Route::match(['get', 'post'], 'callback', 'callback');
    });

// [SECURITY] Removed public GET /command/{command} route that ran arbitrary
// Artisan commands with zero authentication (RCE). See docs/SECURITY_ISSUES.md (C1).




// // routes/api.php
// Route::get('/debug', function () {
//     return response()->json([
//         'marker' => 'LOCAL_BACKEND_v1',
//         'app_url' => env('APP_URL'),
//         'timestamp' => now()->toDateTimeString(),
//     ]);
// });


// [SECURITY] Removed duplicate unauthenticated POST /checkout (see docs/SECURITY_ISSUES.md H5).
// The only checkout entry point is the authenticated POST /payment/checkout above.
Route::get('webhook', [PaymentController::class, 'webhook']); // HyperPay shopper return URL (resourcePath hardened — see M8)
