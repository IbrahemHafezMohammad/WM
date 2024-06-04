<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Constants\GlobalConstants;
use App\Constants\BankCodeConstants;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentCategoryConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterUserPaymentMethodRequest extends FormRequest
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
            'bank_code_id' => ['required', 'integer', Rule::exists(BankCodeConstants::TABLE_NAME, 'id')],
            'payment_category_id' => ['required', 'integer', Rule::exists(PaymentCategoryConstants::TABLE_NAME, 'id')],
            'account_number' => ['required', 'string', 'max:255'],
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

    public function getUserPaymentMethodData()
    {
        $validated = $this->validated();

        return [
            'bank_code_id' => $validated['bank_code_id'],
            'payment_category_id' => $validated['payment_category_id'],
            'account_number' => $validated['account_number'],
            'user_id' => Auth::user()->id,
            'account_name' => Auth::user()->name,
            'is_active' => true,
            'currency' => Auth::user()->player->wallet->currency
        ];
    }
}
