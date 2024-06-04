<?php

namespace App\Constants;

class PaymentMethodHistoryConstants
{
    const TABLE_NAME = 'payment_method_histories';

    const IS_WITHDRAWAL = 1;

    const STATUS_SUCCESS = 1;
    const STATUS_FAILURE = 2;
    const STATUS_PENDING = 3;

    public static function getStatuses()
    {
        return [
            static::STATUS_SUCCESS => 'success',
            static::STATUS_FAILURE => 'failure',
            static::STATUS_PENDING => 'pending',
        ];
    }

    public static function getStatus($statusValue)
    {
        return static::getStatuses()[$statusValue] ?? null;
    }
}