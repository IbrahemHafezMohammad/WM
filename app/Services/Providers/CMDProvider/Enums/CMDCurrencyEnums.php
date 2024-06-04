<?php

namespace App\Services\Providers\CMDProvider\Enums;

enum CMDCurrencyEnums: string
{
    case VNDK = 'VD';

    case PHP = 'PHP';

    case INR = 'INR';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}