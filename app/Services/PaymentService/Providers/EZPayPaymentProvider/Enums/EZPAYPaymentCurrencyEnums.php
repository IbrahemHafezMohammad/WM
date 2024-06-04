<?php

namespace App\Services\PaymentService\Providers\EZPayPaymentProvider\Enums;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Log;

enum EZPAYPaymentCurrencyEnums: string
{
    case VNDK = 'VNDK';

    case PHP = 'PHP';

    case INR = 'INR';

    public static function mapCurrencyToEnum(int $currency)
    {
        Log::info("Transaction Currency is $currency");
        return match ($currency) {
            GlobalConstants::CURRENCY_INR => self::INR,
            GlobalConstants::CURRENCY_VNDK => self::VNDK,
            GlobalConstants::CURRENCY_PHP => self::PHP,
        };
    }
}