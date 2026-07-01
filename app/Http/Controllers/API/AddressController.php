<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Addresses\DeleteAddressRequest;
use App\Http\Requests\Addresses\StoreAddressRequest;
use App\Http\Resources\AddressResource;
use App\Services\AddressService;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{

    public function __construct(protected readonly AddressService $addressService)
    {
    }

    public function index(): JsonResponse
    {
        $addresses = $this->addressService->all();
        return response()->json([
            'addresses' => AddressResource::collection($addresses),
            'status' => true
        ]);
    }

    public function store(StoreAddressRequest $storeAddressRequest): JsonResponse
    {
        $data = $this->addressService->create($storeAddressRequest->all());
        if (!$data->status)
            return response()->json([
                'status' => true,
                'message' => $data->message
            ]);
        return response()->json([
            'address' => new AddressResource($data->model),
            'status' => true
        ]);
    }

    public function destroy(DeleteAddressRequest $deleteAddressRequest): JsonResponse
    {
        $this->addressService->destroy($deleteAddressRequest->address_id);
        return response()->json([
            'message' => trans('app.done'),
            'status' => true
        ]);
    }
}
