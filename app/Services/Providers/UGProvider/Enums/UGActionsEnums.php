<?php

namespace App\Services\Providers\UGProvider\Enums;

enum UGActionsEnums: string
{
    case LOGIN = 'login';
    case GET_BALANCE = 'getBalance';
    case CHANGE_BALANCE = 'changeBalance';
    case CANCEL_TRANSACTION = 'cancelTransaction';
    case CHECK_TRANSACTION = 'checkTransaction';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}