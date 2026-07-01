<?php

namespace App\Enums;

enum SettingKey: string
{

    case TAX = "tax";

    case TERMS = "terms_and_conditions";
    case PRIVACY = "privacy_policy";
    case ABOUT_US = "about_us";

    case HELP_SUPPORT = "help_and_support";

    case PLATFORM_FEES = "platform_fees";

    case VAT = "vat";
    case CALL_CENTER = "call_center_call";
    case ARTIST_ACKNOWLEDGEMENT = "artist_acknowledgement";
}
