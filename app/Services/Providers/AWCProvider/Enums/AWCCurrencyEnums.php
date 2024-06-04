<?php

namespace App\Services\Providers\AWCProvider\Enums;

enum AWCCurrencyEnums: string
{
    case VNDK = 'VND';

    case PHP = 'PHP';

    case INR = 'INR';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}