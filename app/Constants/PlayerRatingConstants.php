<?php

namespace App\Constants;

class PlayerRatingConstants
{
    const TABLE_NAME = 'player_ratings';

    const DEPARTMENT_RISK = 1;
    const DEPARTMENT_FA = 2;

    static public function getDepartments()
    {
        return [
            static::DEPARTMENT_RISK => 'RISK',
            static::DEPARTMENT_FA => 'FA',
        ];
    }
}
