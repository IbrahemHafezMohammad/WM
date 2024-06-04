<?php

namespace App\Constants;

class PlayerBalanceHistoryConstants
{
    const TABLE_NAME = 'player_balance_histories';

    const STATUS_SUCCESS = 1;
    const STATUS_FAILURE = 2;
    const STATUS_PENDING = 3;
    const STATUS_PROCESSING = 4;

    public static function getStatuses()
    {
        return [
            static::STATUS_SUCCESS => 'success',
            static::STATUS_FAILURE => 'failure',
            static::STATUS_PENDING => 'pending',
            static::STATUS_PROCESSING => 'processing',
        ];
    }

    public static function getStatus($statusValue)
    {
        return static::getStatuses()[$statusValue] ?? null;
    }
}