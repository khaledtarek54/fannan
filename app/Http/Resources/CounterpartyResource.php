<?php

namespace App\Http\Resources;

use App\Enums\FileType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * [SECURITY][R2-H1 / R2-H3] Privacy-preserving view of the *other* party in an order/bidding
 * response. It never exposes fcm_token, tax IDs (vat/cr), date of birth or gender, and it exposes
 * contact details (email / phone / whatsapp) ONLY when $withContact is true — i.e. for a confirmed
 * engagement where the caller legitimately needs to reach the counterparty. Browse/list responses
 * pass $withContact = false, so enumerating orders/offers no longer harvests other users' PII.
 */
class CounterpartyResource extends JsonResource
{
    public function __construct($resource, private readonly bool $withContact = false)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'city' => $this->city ?: $this->cityRelation?->name,
            'profile' => $this->profile_photo ? Storage::url($this->profile_photo) : "",
            'completed_profile' => (bool) $this->completed_profile,
            'average_rate' => (float) $this->rating_value,
            'rates' => $this->ratings->count(),
            'from_range' => $this->random_range?->from ?? 0,
            'to_range' => $this->random_range?->to ?? 0,
            'categories' => $this->categories_names,
            'videos_count' => $this->works->where('type', FileType::VIDEO->value)->count(),
            'images_count' => $this->works->where('type', FileType::IMAGE->value)->count(),
            // Social handles are promotional/public; the WhatsApp *number* is gated below as contact.
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'youtube' => $this->youtube,
            'snapchat' => $this->snapchat,
        ];

        if ($this->withContact) {
            $data['email'] = $this->email;
            $data['phone'] = $this->phone;
            $data['phone_prefix'] = $this->phone_prefix;
            $data['whatsapp'] = $this->whatsapp;
        }

        return $data;
    }
}
