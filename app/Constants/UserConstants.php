<?php

namespace App\Constants;

class UserConstants
{
    const TABLE_NAME = 'users';

    //gender
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_OTHER = 3;
    const GENDER_UNKNOWN = 4;

    public static function getGenders()
    {
        return [
            static::GENDER_MALE => 'male',
            static::GENDER_FEMALE => 'female',
            static::GENDER_OTHER => 'other',
            static::GENDER_UNKNOWN => 'unknown'
        ];
    }

    public static function getGender($genderValue)
    {
        return static::getGenders()[$genderValue] ?? null;
    }
}