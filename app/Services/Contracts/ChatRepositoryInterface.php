<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface ChatRepositoryInterface extends BaseRepositoryInterface
{

    public function chatMessages(array $payload):Collection;

}
