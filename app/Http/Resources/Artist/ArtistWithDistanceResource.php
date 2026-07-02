<?php

namespace App\Http\Resources\Artist;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArtistWithDistanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $client = auth()->user();
        $distance = 0.0;

        if ($client && $client->latitude && $client->longitude && $this->latitude && $this->longitude) {
            $distance = $this->calculateDistance(
                (float)$client->latitude,
                (float)$client->longitude,
                (float)$this->latitude,
                (float)$this->longitude
            );
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'profile_image' => $this->profile_photo ? Storage::url($this->profile_photo) : url('/images/logo-gold.png'),
            'category' => $this->categories_names,
            'address' => $this->city ?? $this->cityRelation?->name,
            'latitude' => (float)$this->latitude,
            'longitude' => (float)$this->longitude,
            'distance_km' => round($distance, 2),
        ];
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Radius of the Earth in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
