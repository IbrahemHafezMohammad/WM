<?php

namespace App\Services\Providers\PinnacleProvider\Enums;

enum PinnacleCurrencyEnums: string
{
    case VNDK = 'vndk';

    case PHP = 'php';

    case INR = 'inr';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}