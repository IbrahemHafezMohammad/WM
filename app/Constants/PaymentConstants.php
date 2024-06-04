<?php

namespace App\Constants;

class PaymentConstants
{
       //status
       const IEBANK = 1;
       const SPAY = 2;
       const QRCODE = 3;
   
       public static function getStatuses()
       {
           return [
               static::IEBANK => 'IE_BANK',
               static::SPAY => 'S_PAY',
               static::QRCODE => 'QR_CODE'
           ];
       }
}