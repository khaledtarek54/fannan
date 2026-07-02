<?php

use App\Http\Controllers\API\ArtistController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// [CLEANUP] Removed dead route wired to a non-existent App\Http\Controllers\EasyKashController
// (route name 'easykash.return' is referenced nowhere; it broke route:cache). The live EasyKash
// return flow is /api/easykash/callback. Point EASYKASH_REDIRECT_URL there if a return page is needed.

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
// [SECURITY] Removed debug routes /test-mail and /firebase-test. The latter was public and
// returned every Firebase user UID (plus raw exception messages); the former rendered an email
// for a hardcoded User::find(8). See docs/CODE_REVIEW_FINDINGS.md B8.
