<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTransactionsForPlayerRequest extends FormRequest
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
            'isWithdraw' => 'boolean',
            'status'=>'integer',
            'from_date' => 'date_format:Y-m-d H:i:s',
            'to_date' => 'date_format:Y-m-d H:i:s',
        ];
    }
}
