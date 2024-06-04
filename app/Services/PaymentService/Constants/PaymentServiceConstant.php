<?php

namespace App\Services\PaymentService\Constants;

class PaymentServiceConstant
{
    //status
    const STATUS_SUCCESS = 1;
    const STATUS_WAIT_FOR_PLAYER_PAYMENT = 2;
    const STATUS_FAILED = 3;
    const WAIT_FOR_SERVICE_PAYMENT=4;
    const STATUS_PENDING = 5;
}