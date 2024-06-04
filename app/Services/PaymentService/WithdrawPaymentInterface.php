<?php

namespace App\Services\PaymentService;

use App\Services\PaymentService\DTOs\WithdrawCallbackDTO;
use App\Services\PaymentService\DTOs\WithdrawDTO;

interface WithdrawPaymentInterface {
    public function makeWithdrawRequest() :WithdrawDTO;
    public function processWithdrawCallback($data) :WithdrawCallbackDTO;
}
