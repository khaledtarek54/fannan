<?php

use App\Http\Controllers\EasyKashController;
use App\Http\Controllers\API\ArtistController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Kreait\Firebase\Factory;

Route::get('/', function () {
    return redirect('/admin');
});


Route::get('payments/easykash/return', [EasyKashController::class, 'returnRedirect'])->name('easykash.return');

Route::get('privacy-policy', [SettingController::class, 'privacy'])->name('front.privacy');
Route::get('terms', [SettingController::class, 'terms'])->name('front.terms');
Route::get('about', [SettingController::class, 'about'])->name('front.about');
Route::get('contact', [SettingController::class, 'contact'])->name('front.contact');
Route::post('contact-store', [SettingController::class, 'storeContact'])->middleware('throttle:6,1')->name('front.contact.store');
Route::get('delete-account', [UserController::class, 'deleteAccountView']);
Route::post('delete', [UserController::class, 'deleteUserAccount'])->middleware('throttle:6,1')->name('front.deleteAccount');
Route::get('artist-register', [ArtistController::class, 'webRegister']);
Route::post('artist-store', [UserController::class, 'storeArtist'])->middleware('throttle:6,1')->name('user.register');
Route::get('test-mail', function () {
    $user = User::find(8);
    return view('emails.welcome', ['user' => $user]);
});

/*
|--------------------------------------------------------------------------
| Firebase Test Route
|--------------------------------------------------------------------------
| Visit http://localhost:8000/firebase-test to check if Laravel is connected
| to Firebase successfully. This will list user UIDs (if any) from Firebase Auth.
*/
Route::get('/firebase-test', function () {
    try {
        $factory = (new Factory)->withServiceAccount(config('firebase.projects.app.credentials.file'));
        $auth = $factory->createAuth();

        $users = [];
        foreach ($auth->listUsers() as $user) {
            $users[] = $user->uid;
        }

        return response()->json([
            'status' => 'connected ✅',
            'users_count' => count($users),
            'users' => $users,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error ❌',
            'message' => $e->getMessage(),
        ], 500);
    }
});
