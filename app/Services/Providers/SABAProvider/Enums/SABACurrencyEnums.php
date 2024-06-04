<?php

namespace App\Services\Providers\SABAProvider\Enums;

enum SABACurrencyEnums: int
{
    case VNDK = 20;

    case INR = 21;

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}