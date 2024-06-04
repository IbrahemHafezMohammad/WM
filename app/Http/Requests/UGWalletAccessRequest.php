<?php

namespace App\Http\Requests;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Validation\Rule;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\UGProvider\UGProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\Providers\UGProvider\Enums\UGActionsEnums;

class UGWalletAccessRequest extends FormRequest
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
        $wallet_action = $this->route('wallet_action');

        $rules = [];

        if ($wallet_action == UGActionsEnums::LOGIN) {

            $rules['userId'] = ['required', 'string', 'max:50'];
            $rules['token'] = ['nullable', 'string', 'max:256'];
        }

        if ($wallet_action == UGActionsEnums::GET_BALANCE) {

            $rules['userId'] = ['required', 'string', 'max:50'];
        }

        if ($wallet_action == UGActionsEnums::CHANGE_BALANCE) {

            $rules['data'] = ['required', 'array'];
            $rules['data.*.userId'] = ['required', 'string', 'max:50'];
            $rules['data.*.amount'] = ['required', 'decimal:0,8'];
            $rules['data.*.ticketId'] = ['required', 'string', 'max:15'];
            $rules['data.*.txnId'] = ['required', 'integer'];
            $rules['data.*.bet'] = ['required', 'boolean'];
            $rules['data.*.changeType'] = ['required', 'string', Rule::in(UGProvider::getChangeTypes())];
        }

        if ($wallet_action == UGActionsEnums::CANCEL_TRANSACTION) {
            $rules['data'] = ['required', 'array'];
            $rules['data.*.userId'] = ['required', 'string', 'max:50'];
            $rules['data.*.ticketId'] = ['required', 'string', 'max:15'];
            $rules['data.*.txnId'] = ['required', 'integer'];
        }

        if ($wallet_action == UGActionsEnums::CHECK_TRANSACTION) {
            $rules['data'] = ['required', 'array'];
            $rules['data.*.userId'] = ['required', 'string', 'max:50'];
            $rules['data.*.txnId'] = ['required', 'integer'];
        }

        return $rules;
    }

    public function getStartTime()
    {
        return $this->attributes->get('request_start_time', null);
    }

    protected function failedValidation(Validator $validator)
    {
        $result = UGProvider::validationError($validator->errors()->first());

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_UG)->first();

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
