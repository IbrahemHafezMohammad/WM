<?php

namespace App\Services\PaymentService\Providers;

use App\Helpers\BankCodesHelper;
use App\Models\Account;
use App\Services\PaymentService\DepositPaymentInterface;
use App\Services\PaymentService\WithdrawPaymentInterface;
use Illuminate\Support\Facades\Http;

class DefaultPaymentProvider implements  DepositPaymentInterface, WithdrawPaymentInterface
{

    public string $transactionId;


    public function __construct($transactionId)
    {
        $this->transactionId = (string) $transactionId;
    }

    public function sendPaymentRequest(): string{
        return "SUCCESS";
    }

    public function processWithdrawCallback($request){

    }

}
