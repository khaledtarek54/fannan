<?php

use App\Http\Controllers\EasyKashController;
use App\Http\Controllers\API\ArtistController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// [LOCAL ONLY] Invoice design preview — iterate on the invoice without auth or the mobile app.
//   /invoice/preview           newest order (or built-in sample data on an empty DB), as HTML
//   /invoice/preview/{order}   a specific order, as HTML
//   /invoice/preview?pdf=1     the same, rendered as the real PDF
// The controller also asserts app()->environment('local'); this guard is a second gate.
if (app()->environment('local')) {
    Route::get('invoice/preview/{order?}', [InvoiceController::class, 'preview'])->name('invoice.preview');
}


Route::get('payments/easykash/return', [EasyKashController::class, 'returnRedirect'])->name('easykash.return');

Route::get('privacy-policy', [SettingController::class, 'privacy'])->name('front.privacy');
Route::get('terms', [SettingController::class, 'terms'])->name('front.terms');
Route::get('about', [SettingController::class, 'about'])->name('front.about');
Route::get('contact', [SettingController::class, 'contact'])->name('front.contact');
Route::post('contact-store', [SettingController::class, 'storeContact'])->middleware('throttle:6,1')->name('front.contact.store');
Route::get('delete-account', [UserController::class, 'deleteAccountView']);
Route::post('delete', [UserController::class, 'deleteUserAccount'])->middleware('throttle:6,1')->name('front.deleteAccount');
Route::post('delete-account/send-code', [UserController::class, 'sendDeletionCode'])->middleware('throttle:6,1')->name('front.deleteAccount.sendCode');
Route::get('artist-register', [ArtistController::class, 'webRegister']);
Route::post('artist-store', [UserController::class, 'storeArtist'])->middleware('throttle:6,1')->name('user.register');

// [SECURITY] Removed debug routes `test-mail` and `/firebase-test` (B8). The latter listed every
// Firebase Auth user UID with no authentication. These were reintroduced by the production baseline
// and are not needed in production. See docs/SECURITY_ISSUES.md B8.
