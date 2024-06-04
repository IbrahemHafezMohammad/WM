<?php

namespace App\Services\Providers\GeminiProvider\Enums;

enum GeminiCurrencyEnums: string
{
    case PHP = 'php';
    // case BTC = 'BTC';
    // case VNDK = 'VND_1000';
    // case INR = 'INR';
    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}