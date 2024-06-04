<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListGameTransactionHistoriesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('View Game Transaction Histories');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'from_date' => 'date_format:Y-m-d H:i:s',
            'to_date' => 'date_format:Y-m-d H:i:s',
            'min_points' => 'decimal:0,2',
            'max_points' => 'decimal:0,2',
            'user_name' => 'string',
            'isWithdraw' => 'boolean',
        ];
    }
}
