<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Constants\GlobalConstants;
use App\Constants\BankCodeConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Constants\PaymentCategoryConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\PaymentService\PaymentServiceEnum;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Create Payment Method');
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
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
            'en_public_name' => ['required', 'string', 'max:255'],
            'vn_public_name' => ['nullable', 'string', 'max:255'],
            'tl_public_name' => ['nullable', 'string', 'max:255'],
            'hi_public_name' => ['nullable', 'string', 'max:255'],
            'allow_deposit' => 'boolean',
            'allow_withdraw' => 'boolean',
            'under_maintenance' => 'boolean',
            'api_key' => ['nullable', 'string', 'max:255'],
            'callback_key' => ['nullable', 'string', 'max:255'],
            'api_url' => ['nullable', 'string', 'max:255'],
            'remark' => ['nullable', 'string'],
            'balance' => ['required', 'decimal:0,8'],
            'max_daily_amount' => ['nullable', 'decimal:0,8'],
            'max_total_amount' => ['nullable', 'decimal:0,8'],
            'min_deposit_amount' => ['required', 'decimal:0,8'],
            'max_deposit_amount' => ['required', 'decimal:0,8'],
            'min_withdraw_amount' => ['required', 'decimal:0,8'],
            'max_withdraw_amount' => ['required', 'decimal:0,8'],
            'currency' => ['required', 'integer', Rule::in(array_keys(GlobalConstants::getCurrencies()))],
            'payment_code' => ['required', new Enum(PaymentServiceEnum::class)],
            'internal_name' => ['required'],
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
    
    public function getPaymentMethodData()
    {
        $validated = $this->validated();

        $public_names = [
            'en_public_name' => $validated['en_public_name'],
            'vn_public_name' => $validated['vn_public_name'] ?? null,
            'tl_public_name' => $validated['tl_public_name'] ?? null,
            'hi_public_name' => $validated['hi_public_name'] ?? null,
        ];

        return [
            'bank_code_id' => $validated['bank_code_id'] ?? null,
            'payment_category_id' => $validated['payment_category_id'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'public_name' => $public_names,
            'under_maintenance' => $validated['under_maintenance'] ?? false,
            'allow_deposit' => $validated['allow_deposit'] ?? true,
            'allow_withdraw' => $validated['allow_withdraw'] ?? true,
            'api_key' => $validated['api_key'] ?? null,
            'callback_key' => $validated['callback_key'] ?? null,
            'api_url' => $validated['api_url'] ?? null,
            'remark' => $validated['remark'] ?? null,
            'balance' => $validated['balance'],
            'max_daily_amount' => $validated['max_daily_amount'],
            'max_total_amount' => $validated['max_total_amount'],
            'min_deposit_amount' => $validated['min_deposit_amount'],
            'max_deposit_amount' => $validated['max_deposit_amount'],
            'min_withdraw_amount' => $validated['min_withdraw_amount'],
            'max_withdraw_amount' => $validated['max_withdraw_amount'],
            'payment_code' => $validated['payment_code'],
            'currency' => $validated['currency'],
            'internal_name' => $validated['internal_name']
        ];
    }
}
