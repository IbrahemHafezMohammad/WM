<?php

namespace App\Services\Providers\SSProvider\Enums;

enum SSCurrencyEnums: string
{
    case VNDK = 'VNDK';

    case PHP = 'PHP';

    case INR = 'INR';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}