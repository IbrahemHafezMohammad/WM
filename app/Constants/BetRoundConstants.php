<?php

namespace App\Constants;

class BetRoundConstants
{
    const TABLE_NAME = 'bet_rounds';

    
    const STATUS_OPEN = 1;
    const STATUS_REOPEN = 2;
    const STATUS_CLOSED = 3;
    const STATUS_RECLOSED = 4;

    public static function getStatuses()
    {
        return [
            static::STATUS_OPEN => 'Open',
            static::STATUS_REOPEN => 'Reopen',
            static::STATUS_CLOSED => 'Closed',
            static::STATUS_RECLOSED => 'Reclosed',
        ];
    }

    public static function getStatus($statusValue)
    {
        return static::getStatuses()[$statusValue] ?? null;
    }
}