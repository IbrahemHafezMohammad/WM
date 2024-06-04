<?php

namespace App\Services\Providers\DS88Provider\Enums;

enum DS88ActionsEnums: string
{
    case BALANCE = 'balance';
    case BET = 'bet';
    case CANCEL = 'cancel';
    case SETTLE = 'settle';

    public static function getValidationPattern(): string {
        return collect(self::cases())->map(fn($case) => $case->value)->join('|');
    }
}