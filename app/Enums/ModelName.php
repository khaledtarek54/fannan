<?php

namespace App\Enums;

enum ModelName: string
{
    case ORDER = "order";
    case BIDDING_ORDER_ARTIST = "bidding_order_artist";

    case CHAT = "chat";
    case NOTIFICATION = "App\Models\Notification";
}
