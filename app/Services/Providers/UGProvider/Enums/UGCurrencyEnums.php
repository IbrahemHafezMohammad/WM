<?php

namespace App\Services\Providers\UGProvider\Enums;

enum UGCurrencyEnums: string
{
    case VNDK = '202';

    case PHP = '119';

    case INR = '121';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}