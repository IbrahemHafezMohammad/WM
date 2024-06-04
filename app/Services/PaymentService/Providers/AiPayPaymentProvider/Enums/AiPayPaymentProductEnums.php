<?php

namespace App\Services\PaymentService\Providers\AiPayPaymentProvider\Enums;
use App\Constants\BankCodeConstants;

enum AiPayPaymentProductEnums: string
{
    case GCASH_SCAN = '8026';

    case GCASH_NATIVE = '8029';

    public static function mapCodeToEnum(int $bank_code) {
        return match($bank_code) {
            BankCodeConstants::CODE_GCASH => self::GCASH_SCAN,
            default => null
        };
    }
}