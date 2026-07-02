<?php

namespace App\Services;

use App\Models\Address;
use App\Models\City;
use App\Services\Concerns\CityRepository;
use App\Services\Contracts\AddressRepositoryInterface;
use App\Services\Contracts\CityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use stdClass;

class AddressService
{
    public function __construct(
        protected readonly AddressRepositoryInterface $addressRepository,
        protected readonly CityRepositoryInterface    $cityRepository,
    )
    {
    }

    public function all(): LengthAwarePaginator
    {
        return $this->addressRepository->index();
    }

    /**
     * @param array $payload
     * @return stdClass
     */
    public function create(array $payload): stdClass
    {
        $data = new stdClass();
        $payload['user_id'] = auth()->id();
        $cityName = $this->getCityName($payload['latitude'], $payload['longitude']);
        if (!$cityName) {
            $data->status = false;
            $data->message = trans('app.address_error_try_again');
            return $data;
        }
        $city = $this->getCityIdByName($cityName);
        if (!$city) {
            $data->status = false;
            $data->message = trans('app.address_error_try_again');
            return $data;
        }
        $payload['city_id'] = $city->id;
        /** @var Address $model */
        $model = $this->addressRepository->create($payload);
        $data->status = true;
        $data->model = $model;
        return $data;
    }

    /**
     * @param int $modelId
     * @return bool
     */
    public function destroy(int $modelId): bool
    {
        // [SECURITY] Only delete an address that belongs to the authenticated user (M4 IDOR).
        // Previously deleted by id alone, letting any client delete another client's addresses.
        $address = Address::where('id', $modelId)->where('user_id', auth()->id())->first();
        abort_if($address === null, 403);
        return (bool) $address->delete();
    }

private function getCityName(float $lat, float $lon)
{
    try {
        $apiKey = config('services.map_api_key');
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lon}&sensor=true&key={$apiKey}";

        $response = Http::get($url);
        $data = $response->json();

        if (!empty($data['results'])) {
            foreach ($data['results'] as $result) {
                foreach ($result['address_components'] as $component) {
                    // First try 'locality'
                    if (in_array('locality', $component['types'])) {
                        return $component['long_name'];
                    }
                    // Fallback: administrative_area_level_1
                    if (in_array('administrative_area_level_1', $component['types'])) {
                        return $component['long_name'];
                    }
                }
            }
        }
        return null;
    } catch (\Exception $exception) {
        Log::info('Error in get area: '.$exception->getMessage());
        return null;
    }
}


    private function getCityIdByName(string $cityName): Model|null
    {
        return $this->cityRepository->getCityByName($cityName);
    }

}
