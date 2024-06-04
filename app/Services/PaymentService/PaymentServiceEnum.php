<?php

namespace App\Services\PaymentService;

enum PaymentServiceEnum: int
{

    case IEBANK = 1;
    case SPAY   = 2;
    case AI     = 3;
    case GALAXY = 4;
    case EZPAY  = 5;
    case BANK   = 0;

    public static function toArray()
    {
        return [
            self::AI->value => self::AI->name,
            self::SPAY->value => self::SPAY->name,
            self::IEBANK->value => self::IEBANK->name,
            self::GALAXY->value => self::GALAXY->name,
            self::EZPAY->value => self::EZPAY->name,
            self::BANK->value => self::BANK->name,
        ];
    }
}
