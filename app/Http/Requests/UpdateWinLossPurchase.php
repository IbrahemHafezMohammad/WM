<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Constants\GlobalConstants;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateWinLossPurchase extends FormRequest
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
            'amount' => 'required|numeric',
            'currency' => ['required', 'integer', Rule::in(array_keys(GlobalConstants::getCurrencies()))]
        ];
    }

    public function getWinLossData()
    {
        $validated = $this->validated();

        return [
            'amount' => $validated[ 'amount' ],
            'currency' => $validated[ 'currency' ],
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
