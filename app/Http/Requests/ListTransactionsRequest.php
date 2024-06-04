<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ListTransactionsRequest extends FormRequest
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
            'isWithdraw' => ['sometimes', 'boolean'],
            'status' => ['nullable', 'array'],
            'status.*' => ['nullable', 'integer'],
            'from_date' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'to_date' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'min_amount' => ['sometimes', 'decimal:0,2'],
            'max_amount' => ['sometimes', 'decimal:0,2'],
            'user_name' => ['sometimes', 'string'],
            'payment_method_id' => ['sometimes', 'integer'],
            'agent_code' => ['sometimes', 'string'],
            'transaction_id' => ['sometimes', 'integer'],
            'per_page' => ['nullable', 'integer', Rule::in([1, 10, 25, 50, 100])],
        ];
    }
}
