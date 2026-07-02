<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\DeleteUserAccountRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use App\Services\UserService;
use Illuminate\Http\Request;
use function Livewire\store;

class UserController extends Controller
{
    public function __construct(
        protected readonly UserService       $userService,
        protected readonly SettingRepository $settingRepository,
        protected readonly UserRepository    $userRepository,
    )
    {
    }

    public function deleteAccountView()
    {
        $whatsapp = $this->settingRepository->getWhatsappNumber();
        return view('front.delete-account', compact('whatsapp'));
    }

    public function deleteUserAccount(DeleteUserAccountRequest $deleteUserAccountRequest)
    {
        $status = $this->userService->deleteAccount($deleteUserAccountRequest->all());
        if ($status)
            session()->put('success', 'account deleted successfully');
        else
            session()->put('error', 'account has been deleted before');

        return redirect()->back();
    }

    /**
     * [SECURITY/M7] Regenerate + (best-effort) send the verification code so the deletion form can
     * prove ownership. Delivery depends on the SMS/notification gateway being configured (the OTP
     * notification is currently stubbed — see docs/SECURITY_ISSUES.md M7 and OPEN_QUESTIONS C4).
     */
    public function sendDeletionCode(Request $request)
    {
        $request->validate(['phone' => 'required|exists:users,phone']);

        $user = $this->userRepository->getUserByPhone($request->phone);
        $user->verification_code = self::createVerificationCode();
        $user->save();

        // NOTE: SMS/OTP delivery is not wired in this codebase (there is no OTP notification class).
        // The code is stored and enforced on deletion regardless; the client must deliver it via
        // whatever channel they configure (this is a broader gap — all OTP flows are affected).

        session()->put('success', trans('front.code_sent'));
        return redirect()->back();
    }

    public function storeArtist(StoreUserRequest $storeUserRequest)
    {
        $this->userRepository->store($storeUserRequest);
        session()->put('success', 'Your account has been created. We will send you an email once we launch the app.');
        return redirect()->back();
    }
}
