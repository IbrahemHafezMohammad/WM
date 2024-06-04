<?php

namespace App\Http\Requests;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\ONEProvider\ONEProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\Providers\ONEProvider\Enums\ONEActionsEnums;
use App\Services\Providers\ONEProvider\Enums\ONECurrencyEnums;

class ONEWalletAccessRequest extends FormRequest
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

        if ($wallet_action == ONEActionsEnums::BALANCE) {

            $rules['traceId'] = ['required', 'string'];
            $rules['username'] = ['required', 'string'];
            $rules['currency'] = ['required', 'string'];
            $rules['token'] = ['required', 'string'];
        }

        if ($wallet_action == ONEActionsEnums::BET) {

            $rules['traceId'] = ['required', 'string'];
            $rules['username'] = ['required', 'string'];
            $rules['currency'] = ['required', 'string'];
            $rules['token'] = ['required', 'string'];
            $rules['transactionId'] = ['required', 'string'];
            $rules['betId'] = ['required', 'string'];
            $rules['externalTransactionId'] = ['required', 'string'];
            $rules['amount'] = ['required', 'decimal:0,8'];
            $rules['gameCode'] = ['required', 'string'];
            $rules['roundId'] = ['required', 'string'];
            $rules['timestamp'] = ['required', 'integer'];
        }

        if ($wallet_action == ONEActionsEnums::BET_RESULT) {

            $rules['traceId'] = ['required', 'string'];
            $rules['username'] = ['required', 'string'];
            $rules['transactionId'] = ['required', 'string'];
            $rules['betId'] = ['required', 'string'];
            $rules['externalTransactionId'] = ['required', 'string'];
            $rules['roundId'] = ['required', 'string'];
            $rules['betAmount'] = ['required', 'decimal:0,8'];
            $rules['winAmount'] = ['required', 'decimal:0,8'];
            $rules['effectiveTurnover'] = ['required', 'decimal:0,8'];
            $rules['winLoss'] = ['required', 'decimal:0,8'];
            $rules['jackpotAmount'] = ['nullable', 'decimal:0,8'];
            $rules['resultType'] = ['required', Rule::in(ONEProvider::getBetResultTypes())];
            $rules['isFreespin'] = ['required', 'boolean'];
            $rules['isEndRound'] = ['required', 'boolean'];
            $rules['currency'] = ['required', 'string'];
            $rules['token'] = ['required', 'string'];
            $rules['gameCode'] = ['required', 'string'];
            $rules['betTime'] = ['required', 'integer'];
            $rules['settledTime'] = ['nullable', 'integer'];
        }

        if ($wallet_action == ONEActionsEnums::ROLLBACK) {

            $rules['traceId'] = ['required', 'string'];
            $rules['username'] = ['required', 'string'];
            $rules['transactionId'] = ['required', 'string'];
            $rules['betId'] = ['required', 'string'];
            $rules['externalTransactionId'] = ['required', 'string'];
            $rules['roundId'] = ['required', 'string'];
            $rules['currency'] = ['required', 'string'];
            $rules['gameCode'] = ['required', 'string'];
            $rules['timestamp'] = ['required', 'integer'];
        }

        if ($wallet_action == ONEActionsEnums::ADJUSTMENT) {

            $rules['traceId'] = ['required', 'string'];
            $rules['username'] = ['required', 'string'];
            $rules['transactionId'] = ['required', 'string'];
            $rules['roundId'] = ['required', 'string'];
            $rules['externalTransactionId'] = ['required', 'string'];
            $rules['amount'] = ['required', 'decimal:0,8'];
            $rules['currency'] = ['required', 'string'];
            $rules['gameCode'] = ['required', 'string'];
            $rules['timestamp'] = ['required', 'integer'];
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $result = ONEProvider::validationError($validator->errors()->first(), $this->traceId);

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_ONE)->first();

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
