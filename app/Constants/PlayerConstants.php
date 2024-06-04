<?php

namespace App\Constants;

class PlayerConstants
{
    const TABLE_NAME = 'players';

    const IS_ACTIVE = 1;

    const TYPE_NORMAL = 1;
    const TYPE_TEST = 2;

    public static function getTypes()
    {
        return [
            static::TYPE_NORMAL => 'normal',
            static::TYPE_TEST => 'test',
        ];
    }

    public static function getType($typeValue)
    {
        return static::getTypes()[$typeValue] ?? null;
    }

    const BALANCE = 0;
}