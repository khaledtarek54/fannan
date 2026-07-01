<?php

namespace App\Http\Controllers\API;

use App\Enums\SettingKey;
use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\SettingResource;
use App\Models\Contact;
use App\Models\PriceRange;
use App\Models\Setting;
use App\Repository\SettingRepository;

class SettingController extends BaseController
{

    public function __construct(protected SettingRepository $settingRepository)
    {
    }

    public function index()
    {
        $data = new \stdClass();
        $data->whatsapp = $this->settingRepository->getWhatsappNumber();
//        $data->privacy_policy = config('app.url') . '/privacy-policy';
        $data->privacy_policy = "https://fannan.sa/privacy-policy/";
//        $data->terms_and_conditions = config('app.url') . '/terms';
        $data->terms_and_conditions = "https://fannan.sa/terms-conditions/";
//        $data->about_us = config('app.url') . '/about';
        $data->about_us = "https://fannan.sa/about-us/";
        return $this->sendResponse($data, trans('app.done'));
    }

    public function priceRanges()
    {
        $data = PriceRange::query()->select('id', 'from', 'to')->get();
        return $this->sendResponse($data, trans('app.done'));
    }

    public function terms()
    {
        $whatsapp = $this->settingRepository->getWhatsappNumber();
        $terms = $this->settingRepository->getSettingByKey(SettingKey::TERMS->value);
        return view('front.pages.terms', compact('whatsapp', 'terms'));
    }

    public function privacy()
    {
        $whatsapp = $this->settingRepository->getWhatsappNumber();
        $privacy = $this->settingRepository->getSettingByKey(SettingKey::PRIVACY->value);
        return view('front.pages.privacy', compact('whatsapp', 'privacy'));
    }

    public function about()
    {
        $whatsapp = $this->settingRepository->getWhatsappNumber();
        $about = $this->settingRepository->getSettingByKey(SettingKey::ABOUT_US->value);
        return view('front.pages.about', compact('whatsapp', 'about'));
    }

    public function contact()
    {
        $whatsapp = $this->settingRepository->getWhatsappNumber();
        return view('front.pages.contact', compact('whatsapp'));
    }

    public function storeContact(StoreContactRequest $storeContactRequest)
    {
        $contact = new Contact($storeContactRequest->validated());
        $contact->save();
        session()->flash('success', 'Your message has been sent successfully!');
        return redirect()->back();
    }

    public function artistAcknowledgement()
    {
        $data = $this->settingRepository->getSettingByKey(SettingKey::ARTIST_ACKNOWLEDGEMENT->value);
        return $this->sendResponse($data, trans('app.done'));
    }
}
