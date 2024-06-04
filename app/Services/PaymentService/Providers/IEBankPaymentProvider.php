<?php

namespace App\Services\PaymentService\Providers;

use App\Helpers\BankCodesHelper;
use App\Models\Account;
use App\Services\PaymentService\DepositPaymentInterface;
use App\Services\PaymentService\WithdrawPaymentInterface;
use Illuminate\Support\Facades\Http;

class IEBankPaymentProvider implements  WithdrawPaymentInterface
{

    public string $transactionId;


    public function __construct($transactionId)
    {
        $this->transactionId = (string) $transactionId;
    }

    public function sendPaymentRequest(): string{
            //send request
            //change transaction status
            //make required history
            return "SUCCESS";
    }

    public function processWithdrawCallback($request){
        //parse the callback
        //check if its success and approve transaction
        //check if its failure and reject
        //make required history
    }

}
