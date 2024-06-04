<?php

namespace App\Services\PaymentService;

use App\Services\PaymentService\DTOs\DepositDTO;
use App\Services\PaymentService\DTOs\DepositCallbackDTO;

interface DepositPaymentInterface {

    /*
        1. send request with the info
        2. get the response
        3. change status of transaction accordingly
        4. send formatted json
    */
    public function makeDepositRequest($clientUrl): DepositDTO;
    public function processDepositCallback($request, $transaction) : DepositCallbackDTO;
}
