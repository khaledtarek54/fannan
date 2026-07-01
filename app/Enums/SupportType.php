<?php

namespace App\Enums;

enum SupportType : string
{

    case GENERAL = "general";
    case DIRECT_ORDER = "direct_order";
    case BIDDING_ORDER = "bidding_order";
}
