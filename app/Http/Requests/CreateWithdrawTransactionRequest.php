<?php

namespace App\Http\Requests;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Constants\TransactionConstants;
use Illuminate\Foundation\Http\FormRequest;
use App\Constants\UserPaymentMethodConstants;
use Illuminate\Contracts\Validation\Validator;
use App\Services\PaymentService\PaymentServiceEnum;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateWithdrawTransactionRequest extends FormRequest
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
            'user_payment_method_id' => [
                'required',
                Rule::exists(UserPaymentMethodConstants::TABLE_NAME, 'id')->where('is_active', true)->where('user_id', Auth::user()->id)->where('currency', Auth::user()->player->wallet->currency)
            ],
            'amount' => ['required', 'decimal:0,2'],
        ];


    }

    public function getTransactionData()
    {
        $validated = $this->validated();

        return [
            'user_payment_method_id' => $validated['user_payment_method_id'],
            'amount' => $validated['amount'],
            'isWithdraw' => true,
            'player_id' => Auth::user()->player->id,
            'currency' => Auth::user()->player->wallet->currency
        ];
    }

    public function getWithdrawData(int $id)
    {
        return [
            'transaction_id' => $id,
            'is_fa_locked' => TransactionConstants::FA_OPEN,
            'is_risk_locked' => TransactionConstants::FA_OPEN,
            'risk_action_status' => TransactionConstants::RISK_ACTION_PENDING,
            'reference_no' => $id . Str::random(30 - strlen((string) $id)),
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
