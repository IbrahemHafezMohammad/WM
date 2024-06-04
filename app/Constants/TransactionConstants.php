<?php

namespace App\Constants;

class TransactionConstants
{
    const TABLE_NAME = 'transactions';

    //status
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_PENDING = 3;
    const STATUS_PROCESSING = 4;
    const STATUS_WAITING_FOR_PAYMENT = 5;
    const STATUS_PAYMENT_FAILED = 6;

    //risk status
    const RISK_ACTION_APPROVED = 1;
    const RISK_ACTION_REJECTED = 2;
    const RISK_ACTION_PENDING = 3;
    const  RISK_LOCKED = true;
    const  RISK_OPEN = false;
    const  FA_LOCKED = true;
    const  FA_OPEN = false;

    public static function getStatuses()
    {
        return [
            static::STATUS_APPROVED => 'approved',
            static::STATUS_REJECTED => 'rejected',
            static::STATUS_PENDING => 'pending',
            static::STATUS_PROCESSING => 'processing',
            static::STATUS_WAITING_FOR_PAYMENT => 'Waiting For Payment',
            static::STATUS_PAYMENT_FAILED => 'Failed'
        ];
    }

    public static function getStatus($statusValue)
    {
        return static::getStatuses()[$statusValue] ?? null;
    }

    public static function getRiskStatuses()
    {
        return [
            static::RISK_ACTION_APPROVED => 'approved',
            static::RISK_ACTION_REJECTED => 'rejected',
            static::RISK_ACTION_PENDING => 'pending',
        ];
    }

    public static function getRiskStatus($riskStatusValue)
    {
        return static::getRiskStatuses()[$riskStatusValue] ?? null;
    }
}
