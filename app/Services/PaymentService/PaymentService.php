<?php

namespace App\Services\PaymentService;

use App\Models\User;
use App\Models\BankCode;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentService\Providers\SpayPaymentProvider;
use App\Services\PaymentService\Providers\IEBankPaymentProvider;
use App\Services\PaymentService\Providers\AiPayPaymentProvider\AiPayPaymentProvider;
use App\Services\PaymentService\Providers\GalaxyPaymentProvider\GalaxyPaymentProvider;
use App\Services\PaymentService\Providers\AiPayPaymentProvider\Enums\AiPayPaymentProductEnums;
use App\Services\PaymentService\Providers\AiPayPaymentProvider\Enums\AiPayPaymentCurrencyEnums;
use App\Services\PaymentService\Providers\EZPayPaymentProvider\EZPayPaymentProvider;

class PaymentService
{

    protected PaymentServiceEnum $paymentProvider;
    protected Transaction $transaction;
    protected BankCode $bank_code;


    function __construct(Transaction $transaction, PaymentMethod $paymentMethod = null)
    {

        $this->transaction = $transaction;
        if ($paymentMethod) {
            // Log::info("__construct bank code : " . json_encode($paymentMethod->payment_code));

            $this->paymentProvider = PaymentServiceEnum::tryFrom((int) $paymentMethod->payment_code);

            // Log::info("__construct paymentProvider : " . json_encode($this->paymentProvider));

            $this->bank_code = $paymentMethod->bankCode;
        } else {
            $this->paymentProvider = PaymentServiceEnum::tryFrom((int) $transaction->paymentMethod->payment_code);
            $this->bank_code = $transaction->paymentMethod->bankCode;
        }
    }

    public function getDepositPaymentProvider()
    {
    Log::info("provider ez",[$this->paymentProvider]);

        if ($this->paymentProvider) {
            switch ($this->paymentProvider) {
                case PaymentServiceEnum::IEBANK:
                    return new IEBankPaymentProvider($this->transaction->id);
                case PaymentServiceEnum::SPAY:
                    return new SpayPaymentProvider($this->transaction->id);
                case PaymentServiceEnum::AI:
                    return new AiPayPaymentProvider(
                        $this->transaction,
                        AiPayPaymentProductEnums::mapCodeToEnum($this->bank_code->code),
                        AiPayPaymentCurrencyEnums::mapCurrencyToEnum($this->transaction->currency)
                    );
                case PaymentServiceEnum::GALAXY:
                    return new GalaxyPaymentProvider($this->transaction);
                case PaymentServiceEnum::EZPAY:
                    return new EZPayPaymentProvider($this->transaction);
            }
        }
        return null;
    }

    public function getWithdrawPaymentProvider()
    {
        Log::info("PaymentService: " . json_encode($this->paymentProvider));
        if ($this->paymentProvider) {
            switch ($this->paymentProvider) {
                case PaymentServiceEnum::IEBANK:
                    return new IEBankPaymentProvider($this->transaction->id);
                case PaymentServiceEnum::AI:
                    return new AiPayPaymentProvider(
                        $this->transaction,
                        AiPayPaymentProductEnums::mapCodeToEnum($this->bank_code->code),
                        AiPayPaymentCurrencyEnums::mapCurrencyToEnum($this->transaction->currency)
                    );
                    
                case PaymentServiceEnum::GALAXY:
                    return new GalaxyPaymentProvider($this->transaction);
        }
        }
        return null;
    }
}
