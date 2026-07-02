<?php

namespace App\Repository;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\UserResource;
use App\Mail\WelcomeEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ClientRepository
{

    public function complete($request): \stdClass
    {
        $user = auth()->user();
        $path = $user?->profile_photo;
        if ($request->hasFile('profile_photo') && $request->file('profile_photo'))
            $path = Storage::disk('public')->put('users/', $request->profile_photo);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'dob' => Carbon::parse(Controller::convertArabicDate($request->dob))->format('Y-m-d'),
            'gender' => $request->gender,
            'city_id' => $request->city_id,
            'city' => $request->city,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'vat_number' => $request->vat_number,
            'cr_number' => $request->cr_number,
            'profile_photo' => $path,
            'iban' => $request->iban ?? 0,
            'completed_profile' => true,
            'is_verified' => true,
            'instagram' => $request->instagram ?? null,
            'facebook' => $request->facebook ?? null,
            'youtube' => $request->youtube ?? null,
            'snapchat' => $request->snapchat ?? null,
            'whatsapp' => $request->whatsapp ?? null,
        ]);
        if ($user->role == UserRole::ARTIST->value && $request->start_date && $request->end_date) {
            $user->dates()->delete();
            $startDate = trim($request->start_date, '[]');
            $startDate = explode(',', $startDate);
            $endDate = trim($request->end_date, '[]');
            $endDate = explode(',', $endDate);
            foreach ($startDate as $key => $value) {
                $user->dates()->updateOrCreate(
                    ['start_date' => $value],
                    ['end_date' => $endDate[$key]]
                );
            }
        }

        try {
            Mail::to($user->email)->send(new WelcomeEmail($user));
        } catch (\Exception $exception) {
            Log::info("Error while seing mail: {$exception->getMessage()}");
        }

        $data = new \stdClass();
        $data->user = new UserResource($user);
        $data->status = true;
        return $data;
    }

    public function profile(): ClientResource
    {
        return new ClientResource(auth()->user());
    }

    public function delete(): bool
    {
        $user = auth()->user();
        $user->phone = $user->phone . '-deleted-' . now()->timestamp;
        $user->save();
        $user->delete();
        return true;
    }

}
