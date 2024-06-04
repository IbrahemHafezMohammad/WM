<?php

namespace App\Services\Providers\EVOProvider\Enums;

enum EVOCurrencyEnums: string
{
    case VNDK = 'VN2';

    case PHP = 'PHP';

    case INR = 'INR';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}