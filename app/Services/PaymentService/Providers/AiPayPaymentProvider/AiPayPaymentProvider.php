<?php

namespace App\Services\PaymentService\Providers\AiPayPaymentProvider;

use App\Constants\BankCodeConstants;
use GuzzleHttp\Client;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Constants\GlobalConstants;
use App\Services\PaymentService\Constants\PaymentServiceConstant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use App\Services\PaymentService\DTOs\DepositDTO;
use App\Services\PaymentService\PaymentServiceEnum;
use App\Services\PaymentService\DepositPaymentInterface;
use App\Services\PaymentService\DTOs\DepositCallbackDTO;
use App\Services\PaymentService\DTOs\WithdrawCallbackDTO;
use App\Services\PaymentService\DTOs\WithdrawDTO;
use App\Services\PaymentService\PaymentService;
use App\Services\PaymentService\Providers\AiPayPaymentProvider\Enums\AiPayPaymentProductEnums;
use App\Services\PaymentService\Providers\AiPayPaymentProvider\Enums\AiPayPaymentCurrencyEnums;
use App\Services\PaymentService\WithdrawPaymentInterface;

class AiPayPaymentProvider implements DepositPaymentInterface, WithdrawPaymentInterface
{
    // deposit request responses
    const SUCCESS_RESPONSE_CODE = 20000;

    // deposit request messages
    const DEPOSIT_SUCCESS_MESSAGE = "Success";
    const UNKNOWN_FAILED_MESSAGE = "Failed";
    const API_EXCEPTION_MESSAGE = "AI Payment Provider Call makeDepositRequest API Exception";
    const RESPONSE_SIGNATURE_ERROR = "AI Payment Provider Response Signature Error";
    const INVALID_HTTP_RESPONSE = "Invalid HTTP Response";
    const CUSTOMER_BANK_ERROR = "Customer Bank Invalid";

    // Loan Channels
    const INSTAPAY_CHANNEL = 2000;
    const E_WALLET_CHANNEL = 2001;

    // callback response messages
    const CALLBACK_NOTIFY_MESSAGE_SUCCESS = 'success';
    const CALLBACK_NOTIFY_MESSAGE_FAIL = 'fail';

    // deposit callback statuses
    const DEPOSIT_NOTIFY_ORDER_GENERATED = 0;
    const DEPOSIT_NOTIFY_PAYMENTS = 1;
    const DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL = 2;
    const DEPOSIT_NOTIFY_BUSINESS_PROCESSING_COMPLETED = 3;

    // withdraw callback statuses
    const WITHDRAW_NOTIFY_STATUS_PENDING = 0;
    const WITHDRAW_NOTIFY_STATUS_PROCESSING = 1;
    const WITHDRAW_NOTIFY_STATUS_SUCCESS = 2;
    const WITHDRAW_NOTIFY_STATUS_FAIL = 3;

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

    public function __construct(Transaction $transaction, AiPayPaymentProductEnums $product_enum, AiPayPaymentCurrencyEnums $currency)
    {
        $this->transaction = $transaction;
        $this->mchId = Config::get('app.payment_providers.ai.mchid');
        $this->base_url = Config::get('app.payment_providers.ai.base_url');
        $this->secret_key = Config::get('app.payment_providers.ai.secret_key');
        $this->deposit_notify_url = Config::get('app.payment_providers.deposit_callback_url');
        $this->withdraw_notify_url = Config::get('app.payment_providers.withdraw_callback_url');
        $this->withdraw_url = Config::get('app.payment_providers.ai.withdraw_url');

        $this->currency = $currency;
        $this->product_enum = $product_enum;
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }


    public function makeDepositRequest($clientUrl): DepositDTO
    {
        $data = null;
        $result = null;
        $extra_data = null;

        try {

            $this->transfer_no = $this->transaction->isDepositTransaction->deposit_transaction_no;
            $this->unit_points = self::convertAmountToUnitPoints($this->transaction->amount, $this->currency);

            $data = [
                'amount' => $this->unit_points,
                'mchId' => $this->mchId,
                'productId' => $this->product_enum->value,
                'mchOrderNo' => $this->transfer_no,
                'currency' => $this->currency->value,
                'notifyUrl' => $this->deposit_notify_url . '/' . PaymentServiceEnum::AI->value . '/' . $this->transaction->id,
                'subject' => $this->transaction->remark ?? 'Deposit',
                'returnUrl'=> $clientUrl."/account/transactions",
                'body' => $this->transaction->remark ?? 'Deposit',
            ];

            $generated_signature = $this->generateSignature($data, $this->secret_key);

            $signature = $generated_signature['signature'];

            $extra_data = [
                'request_string_before_hashing' => $generated_signature['string_to_be_hashed'],
                'request_url' => $this->base_url . '/api/v1/collect',
            ];

            $data['sign'] = $signature;

            $response = Http::withHeaders($this->headers)->post($this->base_url . '/api/v1/collect', $data);

            $result = $response->json();

            if (!is_array($result) || !array_key_exists('code', $result)) {
                Log::error($result);
                return new DepositDTO(
                    $this->transaction->id,
                    PaymentServiceConstant::STATUS_FAILED,
                    AiPayPaymentProvider::INVALID_HTTP_RESPONSE,
                    null,
                    $this->transfer_no,
                    null,
                    null,
                    $result,
                    $data,
                    $extra_data
                );
            }

            if ($result['code'] === AiPayPaymentProvider::SUCCESS_RESPONSE_CODE) {

                $response_signature = $result['data']['sign'];

                $response_generated_signature = $this->generateSignature($result['data'], $this->secret_key);
                $extra_data['response_string_before_hashing'] = $response_generated_signature['string_to_be_hashed'];
                if ($response_signature !== $response_generated_signature['signature']) {
                    return new DepositDTO(
                        $this->transaction->id,
                        PaymentServiceConstant::STATUS_FAILED,
                        AiPayPaymentProvider::RESPONSE_SIGNATURE_ERROR,
                        null,
                        $this->transfer_no,
                        null,
                        null,
                        $result,
                        $data,
                        $extra_data
                    );
                }

                $response_data = $result['data'];
                Log::info('-------------------------------------------------------------');
                Log::info("response_data" . json_encode($response_data));
                Log::info('-------------------------------------------------------------');
                $bank_info = [
                    'accountName' => $response_data['bankCardData']['accountName']??null,
                    'accountNo' => $response_data['bankCardData']['accountNo'],
                    'bankName' => $response_data['bankCardData']['bankName'],
                    'orderAmount' => $response_data['orderData']['orderAmount'],
                    'payAmount' => $response_data['orderData']['payAmount'],
                    'channelId' => $response_data['orderData']['channelId'],
                ];

                return new DepositDTO(
                    $this->transaction->id,
                    PaymentServiceConstant::STATUS_WAIT_FOR_PLAYER_PAYMENT,
                    AiPayPaymentProvider::DEPOSIT_SUCCESS_MESSAGE,
                    $response_data['bankCardData']['payment_link'],
                    $this->transfer_no,
                    $response_data['orderData']['payOrderId'],
                    json_encode($bank_info),
                    $result,
                    $data,
                    $extra_data
                );
            }

            Log::info('***************************************************************************************');
            Log::info("request");
            Log::info($data);
            Log::info("response");
            Log::info(json_encode($result));
            Log::info('***************************************************************************************');

            return new DepositDTO(
                $this->transaction->id,
                PaymentServiceConstant::STATUS_FAILED,
                $result['message'] ?? AiPayPaymentProvider::UNKNOWN_FAILED_MESSAGE,
                null,
                $this->transfer_no,
                null,
                null,
                $result,
                $data,
                $extra_data
            );

        } catch (\Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info(AiPayPaymentProvider::API_EXCEPTION_MESSAGE);
            Log::info($exception);
            Log::info('***************************************************************************************');
            Log::info("request");
            Log::info($data);
            Log::info("response");
            Log::info($result);
            Log::info('***************************************************************************************');
            return new DepositDTO(
                $this->transaction->id,
                PaymentServiceConstant::STATUS_FAILED,
                AiPayPaymentProvider::API_EXCEPTION_MESSAGE,
                null,
                $this->transfer_no,
                null,
                null,
                $result,
                $data,
                $extra_data
            );
        }
    }

    private function generateSignature($data, $secret_key)
    {
        unset($data['sign']);

        $flattenedData = $this->flattenData($data);

        ksort($flattenedData);

        $string_to_be_hashed = "";
        foreach ($flattenedData as $key => $value) {
            if ($value !== '' && $value !== null) {
                $string_to_be_hashed .= $key . "=" . $value . "&";
            }
        }

        $string_to_be_hashed = rtrim($string_to_be_hashed, '&');
        $string_to_be_hashed .= "&key=" . $secret_key;

        $signature = md5($string_to_be_hashed);
        $signature = strtoupper($signature);

        return [
            'signature' => $signature,
            'string_to_be_hashed' => $string_to_be_hashed
        ];
    }

    private function flattenData($data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenData($value));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }


    public static function convertAmountToUnitPoints($amount, $currency)
    {
        return match ($currency) {
            AiPayPaymentCurrencyEnums::VNDK => $amount * 100,
            AiPayPaymentCurrencyEnums::PHP => $amount * 100,
            AiPayPaymentCurrencyEnums::INR => $amount * 100,
            default => throw new \Exception('Currency Enum Incorrect')
        };
    }

    public static function convertUnitPointsToAmount($amount, $currency)
    {
        return match ($currency) {
            AiPayPaymentCurrencyEnums::VNDK => $amount / 100,
            AiPayPaymentCurrencyEnums::PHP => $amount / 100,
            AiPayPaymentCurrencyEnums::INR => $amount / 100,
            default => throw new \Exception('Currency Enum Incorrect')
        };
    }

    private static function validateDepositCallback($data): ?array
    {
        $rules = [
            'amount' => ['required'],
            'payer' => ['nullable', 'string', 'max:255'],
            'mchOrderNo' => ['required', 'string', 'max:255'],
            'paySuccTime' => ['nullable', 'max:255'],
            'sign' => ['required', 'string', 'max:255'],
            'status' => ['required', 'integer', 'max:255'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $errors = $validator->errors();
            Log::error("CALLBACK FAILED");
            Log::error(json_encode($errors->all()));
            return null;
        } else {
            return $validator->validated();
        }
    }

    public function generateCallbackResponse(string $message, $status)
    {
        return [
            'message' => $message,
            'status' => $status,
        ];
    }

    public function processDepositCallback($data, $transaction): DepositCallbackDTO
    {
        Log::info('in processDepositCallback');
        Log::info(json_encode($data));
        Log::info($transaction->id);
        Log::info('end processDepositCallback');
        $validated_data = self::validateDepositCallback($data);
        if (!$validated_data) {
            return new DepositCallbackDTO($transaction->id, false, "Invalid Data", null, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false));
        }
        $generated_signature = $this->generateSignature($data, $this->secret_key);
        if ($validated_data['sign'] !== $generated_signature['signature']) {
            return new DepositCallbackDTO($transaction->id, false, "Signature Error", null, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false));
        }
        Log::info('DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL : '.($validated_data['status'] !== self::DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL).'status : '.$validated_data['status'].' DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL : '.self::DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL);
        if ($validated_data['status'] != self::DEPOSIT_NOTIFY_PAYMENT_SUCCESSFUL) {
            return new DepositCallbackDTO($transaction->id, false, "Status Not Success", null, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_SUCCESS, true));
        }
        $amount = self::convertUnitPointsToAmount($validated_data['amount'], $this->currency);
        if ($amount != $transaction->amount) {
            return new DepositCallbackDTO($transaction->id, false, "Amount Mismatch", null, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false));
        }
        //process data
        return new DepositCallbackDTO($transaction->id, true, "AutoApprove: AI", $validated_data['paySuccTime'] ?? null, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_SUCCESS, true));
    }


    public function makeWithdrawRequest(): WithdrawDTO
    {
        try {

            $this->transfer_no = $this->transaction->isWithdrawTransaction->reference_no;
            $this->unit_points = self::convertAmountToUnitPoints($this->transaction->amount, $this->currency);
            $customerPaymentMethod = $this->transaction->userPaymentMethod;
            if (!$customerPaymentMethod || !$customerPaymentMethod->bankCode) {
                Log::info('in makeWithdrawRequest check');
                return new WithdrawDTO(
                    null,
                    PaymentServiceConstant::STATUS_FAILED,
                    AiPayPaymentProvider::CUSTOMER_BANK_ERROR
                );
            }

            $data = [
                'amount' => $this->unit_points,
                'mchId' => $this->mchId,
                'mchOrderNo' => $this->transfer_no,
                'bankName' => $this->getBankNetName($customerPaymentMethod->bankCode->code),
                'passageId' => self::E_WALLET_CHANNEL,
                'bankNetName' => $this->getBankNetName($customerPaymentMethod->bankCode->code),
                'accountName' => $customerPaymentMethod->account_name,
                'accountNo' => $customerPaymentMethod->account_number,
                'reqTime' => now()->format('Ymdhis'),
                'notifyUrl' => $this->withdraw_notify_url . '/' . PaymentServiceEnum::AI->value . '/' . $this->transaction->id
            ];

            $generated_signature = $this->generateSignature($data, $this->secret_key);

            $signature = $generated_signature['signature'];
            $data['sign'] = $signature;

            $response = Http::withHeaders($this->headers)->post($this->base_url . '/api/v1/paying', $data);
            Log::info("http request/response");

            Log::info(json_encode([
                'api_url' => $this->base_url . '/api/v1/paying',
                'request_headers' => $this->headers,
                'request_data' => $data,
                'response_json' => $response->json(),
            ]));

            $result = $response->json();

            if (!is_array($result) || !array_key_exists('code', $result)) {
                return new WithdrawDTO(
                    null,
                    PaymentServiceConstant::STATUS_FAILED,
                    AiPayPaymentProvider::INVALID_HTTP_RESPONSE
                );
            }

            if ($result['code'] === AiPayPaymentProvider::SUCCESS_RESPONSE_CODE) {
                Log::info("code is 20000. lets continue");
                $response_signature = $result['data']['sign'];
                $response_generated_signature = $this->generateSignature($result['data'], $this->secret_key);
                $extra_data['response_string_before_hashing'] = $response_generated_signature['string_to_be_hashed'];
                if ($response_signature !== $response_generated_signature['signature']) {
                    return new WithdrawDTO(
                        null,
                        PaymentServiceConstant::STATUS_FAILED,
                        AiPayPaymentProvider::RESPONSE_SIGNATURE_ERROR
                    );
                }
                if ($result['data']['status'] == 2) {
                    Log::info("status  is 2. lets continue");

                    return new WithdrawDTO(
                        $this->transfer_no,
                        PaymentServiceConstant::STATUS_SUCCESS,
                        AiPayPaymentProvider::DEPOSIT_SUCCESS_MESSAGE
                    );
                }
                if ($result['data']['status'] == 1 || $result['data']['status'] == 0) {
                    Log::info("status  is 1 or 0. lets continue");
                    return new WithdrawDTO(
                        $this->transfer_no,
                        PaymentServiceConstant::WAIT_FOR_SERVICE_PAYMENT,
                        AiPayPaymentProvider::DEPOSIT_SUCCESS_MESSAGE
                    );
                }
                Log::info("status  is failed. lets continue");
                return new WithdrawDTO(
                    $this->transfer_no,
                    PaymentServiceConstant::STATUS_FAILED,
                    AiPayPaymentProvider::UNKNOWN_FAILED_MESSAGE
                );
            }

        } catch (\Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info(AiPayPaymentProvider::API_EXCEPTION_MESSAGE);
            Log::info($exception);
            Log::info('***************************************************************************************');
            Log::info("request");
            Log::info($data);
            Log::info("response");
            Log::info($result);
            Log::info('***************************************************************************************');
        }
        Log::info("status  is failed at the end. lets continue");

        return new WithdrawDTO(
            $this->transfer_no,
            PaymentServiceConstant::STATUS_FAILED,
            AiPayPaymentProvider::UNKNOWN_FAILED_MESSAGE
        );
    }

    protected function getBankNetName($bankCodeConstant)
    {
        switch ($bankCodeConstant) {
            case BankCodeConstants::CODE_GCASH:
                return "P00018";
            default:
                return "P00016";
        }
    }

    private static function validateWithdrawCallbackData($data): ?array
    {
        $rules = [
            'amount' => ['required'],
            'sign' => ['required', 'string', 'max:255'],
            'status' => ['required', 'integer', 'max:255'],
        ];
        
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $errors = $validator->errors();
            Log::error("CALLBACK FAILED");
            Log::error(json_encode($errors->all()));
            return null;
        } else {
            return $validator->validated();
        }
    }

    public function processWithdrawCallback($data): WithdrawCallbackDTO
    {
        $validated_data = self::validateWithdrawCallbackData($data);
        if (!$validated_data) {
            return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_FAILED, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false), "Invalid Data");
        }
        $generated_signature = $this->generateSignature($data, $this->secret_key);
        if ($validated_data['sign'] !== $generated_signature['signature']) {
            return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_FAILED, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false), "Invalid Sign");
        }
        if ($validated_data['status'] == self::WITHDRAW_NOTIFY_STATUS_FAIL) {
            return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_FAILED, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_SUCCESS, true), "Status Failed");
        }
        if (in_array($validated_data['status'], [self::WITHDRAW_NOTIFY_STATUS_PENDING, self::WITHDRAW_NOTIFY_STATUS_PROCESSING])) {
            return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_PENDING, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_SUCCESS, true), "Status Pending");
        }
        $amount = self::convertUnitPointsToAmount($validated_data['amount'], $this->currency);
        if ($amount != $this->transaction->amount) {
            return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_FAILED, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false), "Amount Mismatch");
        }
        if ($validated_data['status'] == self::WITHDRAW_NOTIFY_STATUS_SUCCESS) {
            return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_SUCCESS, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_SUCCESS, true), "success");
        }
        return new WithdrawCallbackDTO(PaymentServiceConstant::STATUS_FAILED, 200, $this->generateCallbackResponse(self::CALLBACK_NOTIFY_MESSAGE_FAIL, false), "Unknown Status");
    }

    public function checkTransactionStatus()
    {
        $data = [
            'mchId' => $this->mchId,
            'mchOrderNo' => $this->transfer_no,
            'reqTime' => now()->format('Ymdhis'),
        ];

        $generated_signature = $this->generateSignature($data, $this->secret_key);
        $signature = $generated_signature['signature'];
        $data['sign'] = $signature;
        $response = Http::withHeaders($this->headers)->post($this->base_url . '/api/v1/paying/query', $data);
        return $response;
    }
}
