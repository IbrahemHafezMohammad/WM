<?php

namespace App\Constants;

class IPWhitelistConstants
{
    const TABLE_NAME = 'whitelist_i_p_s';


    const TYPE_BO=1;
    const TYPE_API=2;
    const TYPE_BO_API=3;

    //getTypes() is a function that returns an array of the constants
    public static function getTypes()
    {
        return [
            static::TYPE_BO => 'BO',
            static::TYPE_API => 'API',
            static::TYPE_BO_API => 'BO & API',
        ];
    }
}
