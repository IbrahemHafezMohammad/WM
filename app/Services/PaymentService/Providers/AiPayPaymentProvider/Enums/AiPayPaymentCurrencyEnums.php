<?php

namespace App\Services\PaymentService\Providers\AiPayPaymentProvider\Enums;
use App\Constants\GlobalConstants;

enum AiPayPaymentCurrencyEnums: string
{
    case VNDK = 'VNDK';

    case PHP = 'PHP';

    case INR = 'INR';

    public static function mapCurrencyToEnum(int $currency)
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_INR => self::INR,
            GlobalConstants::CURRENCY_VNDK => self::VNDK,
            GlobalConstants::CURRENCY_PHP => self::PHP,
        };
    }
}