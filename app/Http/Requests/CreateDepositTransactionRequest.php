<?php

namespace App\Http\Requests;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Constants\TransactionConstants;
use Illuminate\Support\Facades\Storage;
use App\Constants\PaymentMethodConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\PaymentService\PaymentService;
use App\Services\PaymentService\PaymentServiceEnum;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateDepositTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'payment_method_id' => ['required',
                Rule::exists(PaymentMethodConstants::TABLE_NAME, 'id')
                    ->where('under_maintenance', false)
                    ->where('allow_deposit', true)
                    ->where('currency', Auth::user()->player->wallet->currency)
            ],
            'amount' => ['required', 'decimal:0,2'],
        ];
    }

    public function getTransactionData()
    {
        $validated = $this->validated();
        return [
            'player_id' => Auth::user()->player->id,
            'currency' => Auth::user()->player->wallet->currency,
            'amount' => $validated['amount'],
            'isWithdraw' => false,
            'payment_method_id' => $validated['payment_method_id'],
        ];
    }

    public function getDepositData(int $id)
    {
        return [
            'transaction_id' => $id,
            'is_fa_locked' => TransactionConstants::FA_OPEN,
            'deposit_transaction_no' => $id . Str::random(30 - strlen((string) $id)),
        ];
    }
    protected function failedValidation(Validator $validator)
    {

        $response = response()->json([
            'status' => false,
            'message' => $validator->errors(),
        ], 422);

        throw new HttpResponseException($response);
    }
}

