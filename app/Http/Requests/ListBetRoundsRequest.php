<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Constants\GlobalConstants;
use App\Constants\BetRoundConstants;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListBetRoundsRequest extends FormRequest
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
        $validation = [
            'date_type' => ['nullable', 'string', Rule::in(['start', 'end'])],
            'date_from' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'date_to' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'user_name' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'game_name' => ['nullable', 'string'],
            'round_reference' => ['nullable', 'string'],
            'status' => ['nullable', 'integer', Rule::in(array_keys(BetRoundConstants::getStatuses()))],
            'per_page' => ['required', 'integer', Rule::in([10, 25, 50, 100])],
            'game_platform_id' => ['nullable', 'integer', Rule::exists(GamePlatformConstants::TABLE_NAME, 'id')],
            'provider' => ['nullable', 'string'],
            'ip_address' => ['nullable', 'string'],
            'device' => ['nullable', 'string'],
            'currency' => ['nullable', 'integer', Rule::in(array_keys(GlobalConstants::getCurrencies()))],
        ];

        // if ($this->has('date_type') && $this->get('date_type') === 'start') {
        //     $validation['started_from'] = ['required', 'date_format:Y-m-d H:i:s'];
        //     $validation['started_to'] = ['required', 'date_format:Y-m-d H:i:s'];
        // } elseif ($this->has('date_type') && $this->get('date_type') === 'end') {
        //     $validation['ended_from'] = ['required', 'date_format:Y-m-d H:i:s'];
        //     $validation['ended_to'] = ['required', 'date_format:Y-m-d H:i:s'];
        // }

        return $validation;
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
