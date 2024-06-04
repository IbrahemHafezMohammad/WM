<?php

namespace App\Services\Providers\ONEProvider\Enums;

enum ONEActionsEnums: string
{
    case BALANCE = 'balance';
    case BET = 'bet';
    case BET_RESULT = 'bet_result';
    case ROLLBACK = 'rollback';
    case ADJUSTMENT = 'adjustment';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}