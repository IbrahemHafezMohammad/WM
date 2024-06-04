<?php

namespace App\Services\Providers\CMDProvider\Enums;

enum CMDActionsEnums: string
{
    case AUTH_CHECK = 'authCheck';
    case GET_BALANCE = 'getBalance';
    case DEDUCT_BALANCE = 'deductBalance';
    case UPDATE_BALANCE = 'updateBalance';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}