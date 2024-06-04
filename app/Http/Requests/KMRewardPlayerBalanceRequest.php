<?php

namespace App\Http\Requests;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\KMProvider\KMProvider;
use Illuminate\Http\Exceptions\HttpResponseException;

class KMRewardPlayerBalanceRequest extends FormRequest
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
            'transactions' => ['required', 'array'],
            'transactions.*.userid' => ['required', 'string', 'max:50'],
            'transactions.*.amt' => ['required', 'decimal:0,6', 'min:0'],
            'transactions.*.cur' => ['required', 'string', 'between:3,8'],
            'transactions.*.ptxid' => ['required', 'string', 'max:36'],
            'transactions.*.desc' => ['nullable', 'string', 'max:256'],
        ];
    }

    // Override the failedValidation method
    protected function failedValidation(Validator $validator)
    {
        $result = KMProvider::validationError($validator->errors()->first());

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_KM)->first();

        ApiHit::createApiHitEntry(
            $this,
            $response,
            null,
            null,
            $game_platform ?? null,
        );

        throw new HttpResponseException($response);
    }
}
