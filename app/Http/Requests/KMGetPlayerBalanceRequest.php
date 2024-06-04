<?php

namespace App\Http\Requests;

use App\Models\GameItem;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Validation\Rule;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\KMProvider\KMProvider;
use Illuminate\Http\Exceptions\HttpResponseException;

class KMGetPlayerBalanceRequest extends FormRequest
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
            // 'testmode' => ['sometimes', 'nullable', Rule::in(['delay-10', 'delay-5', 'fail-500', 'emptyresponse', 'setbalance:1000'])],
            'users' => ['required', 'array'],
            'users.*.authtoken' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'users.*.userid' => ['required', 'string', 'max:50'],
            'users.*.brandcode' => ['required', 'string', 'max:20'],
            'users.*.lang' => ['sometimes', 'nullable', 'string', 'max:5'],
            'users.*.cur' => ['required', 'string', 'between:3,8'],
            'users.*.walletcode' => ['sometimes', 'nullable', 'string', 'max:20'],
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
