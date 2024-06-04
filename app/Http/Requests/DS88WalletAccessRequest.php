<?php

namespace App\Http\Requests;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Support\Facades\Log;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\DS88Provider\DS88Provider;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\Providers\DS88Provider\Enums\DS88ActionsEnums;

class DS88WalletAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function JWTDecodeRawDataField()
    {
        $requested_currency = $this->route('currency'); // Access route parameter

        $credentials = DS88Provider::getCredential($requested_currency);

        $raw_content = $this->getContent();

        if (is_string($raw_content) && !empty($raw_content)) {

            try {

                $data = JWT::decode($raw_content, new Key($credentials['secret_key'], 'HS512'));

                $data_array = objectToArray($data);

                $this->merge(['all_data' => $data_array]);
               
            } catch (\Exception $e) {
                // Handle decoding errors, possibly set an error message in the request
                Log::error("JWT decoding failed: " . $e);
                // Optionally, you could abort the request here or handle it as you see fit
            }
        }
    }

    protected function getValidatorInstance()
    {
        $this->JWTDecodeRawDataField();
        return parent::getValidatorInstance();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $wallet_action = $this->route('wallet_action');

        $rules = [];

        if ($wallet_action !== DS88ActionsEnums::BALANCE) {

            $rules['all_data'] = ['required', 'array'];
            $rules['all_data.slug'] = ['required', 'string', 'max:50'];
            $rules['all_data.data'] = ['required', 'array'];
            $rules['all_data.data.*.num'] = ['required', 'string', 'max:50'];
            $rules['all_data.data.*.player'] = ['required', 'string', 'max:50'];
            $rules['all_data.data.*.amount'] = ['required', 'decimal:0,6'];
        }


        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $result = DS88Provider::validationError($validator->errors()->first());

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_DS88)->first();

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
