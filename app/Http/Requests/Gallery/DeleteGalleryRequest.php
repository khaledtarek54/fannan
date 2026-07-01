<?php

namespace App\Http\Requests\Gallery;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class DeleteGalleryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return auth()->user()->role == UserRole::ARTIST->value;
    }

    public function rules(): array
    {
        return [
            'gallery_id' => 'required|exists:user_gallery_works,id,user_id,' . auth()->id(),
        ];
    }
}
