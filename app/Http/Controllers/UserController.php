<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\DeleteUserAccountRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use App\Services\UserService;
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

    public function storeArtist(StoreUserRequest $storeUserRequest)
    {
        $this->userRepository->store($storeUserRequest);
        session()->put('success', 'Your account has been created. We will send you an email once we launch the app.');
        return redirect()->back();
    }
}
