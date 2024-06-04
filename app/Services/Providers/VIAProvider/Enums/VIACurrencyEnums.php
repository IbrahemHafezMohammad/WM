<?php

namespace App\Services\Providers\VIAProvider\Enums;

enum VIACurrencyEnums: string
{
    case VNDK = 'vndk';

    case PHP = 'php';

    case INR = 'inr';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}