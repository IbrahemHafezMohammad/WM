<?php

namespace App\Services\PaymentService\Providers\GalaxyPaymentProvider;

use App\Constants\BankCodeConstants;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Services\PaymentService\Constants\PaymentServiceConstant;
use App\Services\PaymentService\DepositPaymentInterface;
use App\Services\PaymentService\DTOs\DepositCallbackDTO;
use App\Services\PaymentService\DTOs\DepositDTO;
use App\Services\PaymentService\DTOs\WithdrawCallbackDTO;
use App\Services\PaymentService\DTOs\WithdrawDTO;
use App\Services\PaymentService\PaymentServiceEnum;
use App\Services\PaymentService\Providers\GalaxyPaymentProvider\Enums\GalaxyPaymentCurrencyEnums;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GalaxyPaymentProvider implements DepositPaymentInterface
{

    // Accepted payment types
    const PAYMENT_TYPE_QRCODE = 1;
    const PAYMENT_TYPE_WEB_H5 = 2;
    const PAYMENT_TYPE_FAST_DIRECT = 3;

    // Error Messages
    const API_EXCEPTION_MESSAGE         = "Galaxy Payment Provider Call makeDepositRequest API Exception";
    const INVALID_HTTP_RESPONSE         = "Invalid HTTP Response";
    const DEPOSIT_TRANSACTION_FAILED    = "Deposit Request Failed. Please try again with a different payment option or please try after some time";

    // withdraw request message
    const WITHDRAW_REQUEST_MESSAGE_SUCCESS = 'Waiting for Payment';
    const WITHDRAW_REQUEST_MESSAGE_FAIL = 'Withdraw Request Failed';

    // call back message
    const CALLBACK_NOTIFY_PAYMENT_SUCCESSFUL = 5;
    const CALLBACK_NOTIFY_MESSAGE_SUCCESS = 'success';
    const CALLBACK_NOTIFY_MESSAGE_FAIL = 'fail';

    // deposit callback statuses
    const DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL = 5;
    const DEPOSIT_SUCCESS_MESSAGE = "Success";

    // Bank Error
    const CUSTOMER_BANK_ERROR = "Customer Bank Invalid";

    protected Transaction $transaction;
    protected $mchId;
    protected $signature;
    protected $base_url;
    protected $unit_points;
    protected $secret_key;
    protected $deposit_notify_url;
    protected $withdraw_notify_url;
    protected $currency;
    protected $transfer_no;
    protected $headers;
    protected $product_enum;
    protected $withdraw_url;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
        $this->mchId = Config::get('app.payment_providers.galaxy.mchid');
        $this->base_url = Config::get('app.payment_providers.galaxy.base_url');
        $this->secret_key = Config::get('app.payment_providers.galaxy.secret_key');
        $this->deposit_notify_url = Config::get('app.payment_providers.deposit_callback_url');
        $this->withdraw_notify_url = Config::get('app.payment_providers.withdraw_callback_url');

        $this->currency = GalaxyPaymentCurrencyEnums::mapCurrencyToEnum($this->transaction->currency);

        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function makeDepositRequest($clientURl): DepositDTO
    {
        $data = null;
        $result = null;
        $extra_data = null;

        try {

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            $this->transfer_no = $this->transaction->isDepositTransaction->deposit_transaction_no;
            $Callback_url = $this->deposit_notify_url . '/' . PaymentServiceEnum::GALAXY->value . '/' . $this->transaction->id;

            Log::info("Call_back_url_before_sending_and_before_hasing",[$Callback_url]);

            $bank_code      = BankCodeConstants::getCode($this->transaction->paymentMethod->bankCode->code);
            $payament_type  = ($bank_code!="PMP") ?  GalaxyPaymentProvider::PAYMENT_TYPE_QRCODE : GalaxyPaymentProvider::PAYMENT_TYPE_FAST_DIRECT; 
            $merchant       = $this->mchId;
            $payment_type   = $payament_type;
            $returnUrl      = $clientURl."/account/transactions";
            $amount         =$this->transaction->amount;

            $data = [
                'amount' => $amount,
                'bank_code' => $bank_code,
                'callback_url' =>$Callback_url,
                'merchant' => $merchant,
                'order_id' => $this->transfer_no,
                'payment_type' => $payment_type,
                'return_url' =>$returnUrl
            ];
            
            $key = $this->secret_key;
            $data['sign'] = $this->generateSignature($data, $key);
            $payment_url=$this->base_url . "/" . "transfer";

            $response = Http::withHeaders($headers)->post($payment_url, $data);
            $result = $response->json();

            Log::info('API Response:', [$result]);
            
            if (isset($result['status']) && $result['status'] == 1) {

                Log::info('API Response Successful:', [$result]);
                $payment_url=$result['qrcode_url'];
                $extra_data['URL']=$Callback_url;
            
                return new DepositDTO(
                    $this->transaction->id,
                    PaymentServiceConstant::STATUS_WAIT_FOR_PLAYER_PAYMENT,
                    GalaxyPaymentProvider::DEPOSIT_SUCCESS_MESSAGE ,
                    $payment_url,
                    $this->transfer_no,
                    null,
                    null,
                    $result,
                    $data,
                    $extra_data
                );

            } else {

                Log::error('Transaction failed:', [$result]);
                return new DepositDTO(
                    $this->transaction->id,
                    PaymentServiceConstant::STATUS_FAILED,
                    GalaxyPaymentProvider::DEPOSIT_TRANSACTION_FAILED,
                    $payment_url,
                    $this->transfer_no,
                    null,
                    null,
                    $result,
                    $data,
                    $extra_data
                );
            }
        } catch (Exception $exception) {
            Log::error("Galaxy Deposit Transaction Exception occurred:", [$exception->getMessage()]);

            return new DepositDTO(
                $this->transaction->id,
                PaymentServiceConstant::STATUS_FAILED,
                GalaxyPaymentProvider::DEPOSIT_TRANSACTION_FAILED,
                $payment_url,
                $this->transfer_no,
                null,
                null,
                $result,
                $data,
                $extra_data
            );
        }
    }

    public  function processDepositCallback($data, $transaction): DepositCallbackDTO
    {
        Log::info('in processDepositCallback');
        Log::info(json_encode($data));
        Log::info($transaction->id);
        Log::info('end processDepositCallback');


        $generated_signature = $this->generateSignature($data, $this->secret_key);
        
        if ($data['sign'] !== $generated_signature) {
            return new DepositCallbackDTO($transaction->id, false, "Signature Error", null, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false));
        }
        $amount = $data['amount'];

        Log::info('DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL : ' . ($data['status'] !== self::DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL) . 'status : ' . $data['status'] . ' DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL : ' . self::DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL);

        if ($data['status'] != self::DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL) {
            return new DepositCallbackDTO($transaction->id, false, "Status Not Success", null, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false));
        }

        if ($amount != $transaction->amount) {
            return new DepositCallbackDTO($transaction->id, false, "Amount Mismatch", null, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false));
        }
        
        return new DepositCallbackDTO($transaction->id, true, self::CALLBACK_NOTIFY_MESSAGE_SUCCESS, null, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_SUCCESS, true));
    }

    public function generateCallbackResponse(string $message, $status)
    {
        return [
            'message' => $message,
            'status' => $status,
        ];
    }

    public function makeWithdrawRequest(): WithdrawDTO
    {
        try {
            $this->transfer_no = $this->transaction->isWithdrawTransaction->reference_no;
            $amount = self::convertUnitPointsToAmount($this->transaction->amount, $this->currency);
            $customerPaymentMethod = $this->transaction->userPaymentMethod;

            if (!$customerPaymentMethod || !$customerPaymentMethod->bankCode) {

                return new WithdrawDTO(
                    null,
                    PaymentServiceConstant::STATUS_FAILED,
                    GalaxyPaymentProvider::CUSTOMER_BANK_ERROR
                );
            }

            $data = [
                'merchant' => $this->mchId,
                'total_amount' => $amount,
                'callback_url' => $this->withdraw_notify_url . '/' . PaymentServiceEnum::GALAXY->value . '/' . $this->transaction->id,
                'order_id' => $this->transfer_no,
                'bank' => $customerPaymentMethod->bankCode->code_name,
                'bank_card_name' => $customerPaymentMethod->account_name,
                'bank_card_account' => $customerPaymentMethod->account_number,
                'bank_card_remark' => 'no',
            ];

            $key = $this->secret_key;
            $data['sign'] = $this->generateSignature($data, $key);
            $requestUrl = $this->base_url . "/" . "daifu";
            $response = Http::withHeaders($this->headers)->post($requestUrl, $data);
            $result = $response->json();

            Log::info('==================================================================Withdraw API Response:===============================================', [$data, $result]);
            if (isset($result['status']) && $result['status'] == 1) {

                Log::info('Sucessful coming ....', [$result]);
                return new WithdrawDTO(
                    $this->transfer_no,
                    PaymentServiceConstant::WAIT_FOR_SERVICE_PAYMENT,
                    GalaxyPaymentProvider::WITHDRAW_REQUEST_MESSAGE_SUCCESS
                );
            }

            Log::info('Failed request  ....', [$result]);

            return new WithdrawDTO(

                $this->transfer_no,
                PaymentServiceConstant::STATUS_FAILED,
                GalaxyPaymentProvider::WITHDRAW_REQUEST_MESSAGE_FAIL
            );

        } catch (Exception $exception) {

            Log::error("Exception occurred:", [$exception->getMessage()]);

            return new WithdrawDTO(
                $this->transfer_no,
                PaymentServiceConstant::STATUS_FAILED,
                GalaxyPaymentProvider::WITHDRAW_REQUEST_MESSAGE_FAIL
            );
        }
    }

    public function processWithdrawCallback($data): WithdrawCallbackDTO
    {

        if ($data['status'] == self::CALLBACK_NOTIFY_PAYMENT_SUCCESSFUL) {

            $generated_signature = $this->generateSignature($data, $this->secret_key);

            if ($data['sign'] !== $generated_signature) {
                return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_FAILED, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false), "Invalid Sign");
            }

            if ($data['amount'] != $this->transaction->amount) {
                return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_FAILED, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false), "Amount Mismatch");
            }

            if ($data['status'] == self::CALLBACK_NOTIFY_PAYMENT_SUCCESSFUL) {
                Log::info("coming with ".$data['status']);
                return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_SUCCESS, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_SUCCESS, true), "success");
            }

        }else{

            return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_FAILED, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false), "failed");
        }

        return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_FAILED, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false), "Unknown Status");
    }

    private function generateSignature($data, $secret_key)
    {

        if(isset($data['sign'])){
            unset($data['sign']);
        }

        ksort($data);
        $finalString = "";
        
        foreach ($data as $key => $value) {
            $finalString .= $key . "=" . $value;
            $finalString .= "&";
        }

        $finalString .= "key=" . $secret_key;
        return md5($finalString);
    
    }

    public static function convertUnitPointsToAmount($amount, $currency)
    {
        return match ($currency) {
            GalaxyPaymentCurrencyEnums::PHP => ($amount),
            default => throw new \Exception('Currency Enum Incorrect')
        };
    }
}
