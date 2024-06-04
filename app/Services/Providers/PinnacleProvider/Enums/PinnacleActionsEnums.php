<?php

namespace App\Services\Providers\PinnacleProvider\Enums;

enum PinnacleActionsEnums: string
{
    case PING = 'ping';
    case BALANCE = 'balance';
    case WAGERING = 'wagering';
    
    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}