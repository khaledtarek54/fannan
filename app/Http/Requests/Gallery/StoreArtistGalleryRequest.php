<?php

namespace App\Http\Requests\Gallery;

use App\Enums\FileType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArtistGalleryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return auth()->user()?->role === UserRole::ARTIST->value;
    }

    public function rules(): array
    {
        $rules = [
            'video' => 'required',
            'is_pin' => 'required|in:1,0',
            // [SECURITY] Constrain the media type to the known set (L3).
            'type' => ['required', Rule::in([FileType::IMAGE->value, FileType::VIDEO->value])],
        ];

        // [SECURITY] When an actual file is uploaded (vs a pre-stored path), restrict its
        // mimetype and size so arbitrary executables/oversized files can't be stored (L3).
        if ($this->hasFile('video')) {
            $rules['video'] = [
                'required', 'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime',
                'max:51200', // 50 MB
            ];
        }

        if ($this->request->has('gallery_id'))
            $rules['gallery_id'] = 'required|exists:user_gallery_works,id,user_id,' . auth()->id();
        return $rules;
    }
}
