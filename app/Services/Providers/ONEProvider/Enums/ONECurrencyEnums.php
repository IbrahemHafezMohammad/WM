<?php

namespace App\Services\Providers\ONEProvider\Enums;

enum ONECurrencyEnums: string
{
    case VNDK = 'VND(K)';

    case PHP = 'PHP';

    case INR = 'INR';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}