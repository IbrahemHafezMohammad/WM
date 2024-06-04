<?php

namespace App\Constants;

class KMProviderConfigConstants
{
    const TABLE_NAME = 'km_provider_configs';

    // Bet Limit
    const BET_LIMIT_BASIC = 1;
    const BET_LIMIT_SILVER = 2;
    const BET_LIMIT_GOLD = 3;
    const BET_LIMIT_PLATINUM = 4;

    public static function getBetLimits()
    {
        return [
            static::BET_LIMIT_BASIC => 'Basic',
            static::BET_LIMIT_SILVER => 'Silver',
            static::BET_LIMIT_GOLD => 'Gold',
            static::BET_LIMIT_PLATINUM => 'Platinum',
            
        ];
    }

    public static function getBetLimit($betLimitValue)
    {
        return static::getBetLimits()[$betLimitValue] ?? null;
    }
}