<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Constants\PaymentMethodConstants;
use Illuminate\Foundation\Http\FormRequest;

class AdjustPaymentMethodRequest extends FormRequest
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
            'payment_method_id' => ['required', 'integer', Rule::exists(PaymentMethodConstants::TABLE_NAME, 'id')],
            'isWithdraw' => ['required', 'boolean'],
            'amount' => ['required', 'decimal:0,2'],
            'remark' => ['nullable', 'string'],
        ];
    }
}
