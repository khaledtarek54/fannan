<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Notification\NotificationResource;
use App\Services\NotificationService;

class NotificationController extends BaseController
{

    public function __construct(protected readonly NotificationService $notificationService)
    {
    }

    public function index()
    {
        $data = NotificationResource::collection($this->notificationService->all());
        return $this->sendResponse($data, trans('app.done'));
    }
}
