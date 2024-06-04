<?php

namespace App\Services\PaymentService\Providers;

use App\Constants\AccountConstants;
use App\Models\Account;
use App\Services\PaymentService\DepositPaymentInterface;
use App\Services\PaymentService\WithdrawPaymentInterface;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use stdClass;

class SpayPaymentProvider implements DepositPaymentInterface, WithdrawPaymentInterface
{

    public string $transactionId;
    const QR_GENERATE_URL = 'http://api.safepmt.africa/spay-service/order/pay';
    const WITHDRAW_URL = 'https://safepmt.africa/api/spay-service/order/pay';


    public function __construct($transactionId)
    {
        $this->transactionId = (string) $transactionId;
    }

    public function generateQR($transactionAmount){
       //send request
       //get qr link
       //return
    }

    public function formatCallback(){
        
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
