<?php

namespace App\Enums;

enum OrderStatus: string
{
    case ARTIST_PENDING = 'artist_pending';

    case NEW = "new";

    case ACCEPTED = 'accepted';

    case COMPLETED = 'completed';

    case REJECTED = 'rejected';
    case IN_PAYMENT = 'in_payment';

    case COUNTER_OFFER = "counter_offer";

    case PENDING = "pending";
    case CANCELED = "canceled";
}
