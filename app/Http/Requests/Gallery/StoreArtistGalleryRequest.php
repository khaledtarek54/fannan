<?php

namespace App\Http\Requests\Gallery;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreArtistGalleryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return auth()->user()->role == UserRole::ARTIST->value;
    }

    public function rules(): array
    {
        $rules = [
            'video' => 'required',
            'is_pin' => 'required|in:1,0',
            'type' => 'required',
        ];
        if ($this->request->has('gallery_id'))
            $rules['gallery_id'] = 'required|exists:user_gallery_works,id,user_id,' . auth()->id();
        return $rules;
    }
}
