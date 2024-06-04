<?php

namespace App\Constants;

class EVOProviderConfigConstants
{
    const TABLE_NAME = 'evo_provider_configs';

    // Bet Limit
    const GROUP_ID_BASIC = '';
    const GROUP_ID_SILVER = '';
    const GROUP_ID_GOLD = '';
    const GROUP_ID_PLATINUM = '';

    public static function getGroupIds()
    {
        return [
            static::GROUP_ID_BASIC => 'Basic',
            static::GROUP_ID_SILVER => 'Silver',
            static::GROUP_ID_GOLD => 'Gold',
            static::GROUP_ID_PLATINUM => 'Platinum',
        ];
    }

    public static function getGroupId($groupIdValue)
    {
        return static::getGroupIds()[$groupIdValue] ?? null;
    }
}