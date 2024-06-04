<?php

namespace App\Services\Providers\EVOProvider\Enums;

enum EVOActionsEnums: string
{
    case CHECK = 'check';
    case BALANCE = 'balance';
    case DEBIT = 'debit';
    case CREDIT = 'credit';
    case CANCEL = 'cancel';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }

    public static function isWalletChangeAction(EVOActionsEnums $action): bool {
        return in_array($action, [self::DEBIT, self::CREDIT, self::CANCEL], true);
    }
}