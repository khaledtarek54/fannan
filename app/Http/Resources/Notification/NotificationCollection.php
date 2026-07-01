<?php

namespace App\Http\Resources\Notification;

use App\Http\Resources\BasePaging;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NotificationCollection extends ResourceCollection
{
    use BasePaging;

    public $collects = 'App\Http\Resources\Notification\NotificationResource';


    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'links' => $this->paginationLinks(),
            'meta' => $this->meta(),
        ];
    }
}
