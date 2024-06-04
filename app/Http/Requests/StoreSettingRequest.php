<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use App\Constants\SettingConstants;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSettingRequest extends FormRequest
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
            'key' => [ 'required', Rule::unique(SettingConstants::TABLE_NAME, 'key') ],
            'value' => [ 'nullable' ]
        ];
    }

    public function getSettingData()
    {
        $validated = $this->validated();

        return [
            'key' => $validated[ 'key' ],
            'value' => $validated[ 'value' ]
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
