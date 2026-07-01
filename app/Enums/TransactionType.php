<?php

namespace App\Enums;

enum TransactionType: string
{
    case INCOME = "income";

    case WITHDRAW = "withdraw";
}
