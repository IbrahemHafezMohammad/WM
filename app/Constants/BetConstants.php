<?php

namespace App\Constants;

class BetConstants
{
    const TABLE_NAME = 'bets';

    
    const STATUS_UNSETTLED = 1;
    const STATUS_SETTLED = 2;
    const STATUS_RESETTLED = 3;
    const STATUS_CANCELED = 4;
    const STATUS_ACCEPTED = 5;

    public static function getStatuses()
    {
        return [
            static::STATUS_UNSETTLED => 'Unsettled',
            static::STATUS_ACCEPTED => 'Accepted',
            static::STATUS_SETTLED => 'Settled',
            static::STATUS_RESETTLED => 'Resettled',
            static::STATUS_CANCELED => 'Canceled',
        ];
    }

    public static function getStatus($statusValue)
    {
        return static::getStatuses()[$statusValue] ?? null;
    }
}