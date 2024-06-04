<?php

namespace App\Http\Requests;

use App\Models\GameItem;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\VIAProvider\VIAProvider;
use Illuminate\Http\Exceptions\HttpResponseException;

class VIAGetPlayerBalanceRequest extends FormRequest
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
            'vendorPlayerId' => ['required', 'string'],
        ];
    }

    // Override the failedValidation method
    protected function failedValidation(Validator $validator)
    {
        $result = VIAProvider::validationError($validator->errors()->first());

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_VIA)->first();

        ApiHit::createApiHitEntry(
            $this,
            $response,
            null,
            null,
            $game_platform,
        );

        throw new HttpResponseException($response);
    }
}
