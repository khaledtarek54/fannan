<?php

namespace App\Enums;

enum OrderType: string
{
    case DIRECT = "direct";

    case BIDDING = "bidding";
}
