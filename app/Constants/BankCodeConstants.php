<?php

namespace App\Constants;

class BankCodeConstants
{
    const TABLE_NAME = 'bank_codes';

    // image validation
    const IMAGE_MAX_SIZE = 10 * 1024;
    const IMAGE_WIDTH = 300;
    const IMAGE_HEIGHT = 300;

    // codes
    const CODE_TECHCOMBANK = 1;
    const CODE_DONGABANK = 2;
    const CODE_ACBBANK = 3;
    const CODE_TPBANK = 4;
    const CODE_GCASH = 5;
    const CODE_PMP = 6;


    static public function getCodes()
    {
        return [
            static::CODE_TECHCOMBANK => 'TECHCOMBANK',
            static::CODE_DONGABANK => 'DONGABANK',
            static::CODE_ACBBANK => 'ACBBANK',
            static::CODE_TPBANK => 'TPBANK',
            static::CODE_GCASH => 'GCASH',
            static::CODE_PMP => 'PMP',
        ];
    }

    static public function getCode($codeValue)
    {
        return static::getCodes()[$codeValue] ?? null;
    }
}