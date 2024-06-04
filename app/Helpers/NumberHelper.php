<?php

namespace App\Helpers;

class NumberHelper
{

    public static function randomNumber($len)
    {
        $res = '';
        for ($i = 0; $i < $len; $i++) {
            $res .= mt_rand(0, 9);
        }
        return $res;
    }
}