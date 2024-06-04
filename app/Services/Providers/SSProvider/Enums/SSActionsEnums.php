<?php

namespace App\Services\Providers\SSProvider\Enums;

enum SSActionsEnums: string
{
    case PING = 'pingapi';
    case GET_BALANCE = 'getcclapi';
    case DEDUCT_BALANCE = 'deductapi';
    case ROLLBACK_TRANSACTION = 'rollbackapi';
    case CHECK_TRANSACTION = 'acknowledgement';
    case SETTLE = 'settleapi';
    case TRACKER = 'trackerapi';
    case PROMOTION = 'promotionapi';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}