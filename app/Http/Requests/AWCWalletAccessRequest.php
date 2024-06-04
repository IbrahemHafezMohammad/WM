<?php

namespace App\Http\Requests;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\AWCProvider\AWCProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\Providers\AWCProvider\Enums\AWCActionEnums;
use App\Services\Providers\AWCProvider\Enums\AWCCurrencyEnums;

class AWCWalletAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function getValidatorInstance()
    {
        $this->decodeMessageField();

        return parent::getValidatorInstance();
    }

    protected function decodeMessageField()
    {
        if ($this->has('message') && is_string($this->message)) {
            $decoded = json_decode($this->message, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['message' => $decoded]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'message' => ['required', 'array'],
            'message.action' => ['required', new Enum(AWCActionEnums::class)],
        ];

        $message_data = $this->message;

        if (!is_null($message_data) && isset($message_data['action'])) {

            if ($message_data['action'] === AWCActionEnums::GET_BALANCE->value) {

                $rules['message.userId'] = ['required', 'string'];

            } elseif ($message_data['action'] === AWCActionEnums::PLACE_BET->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.currency'] = ['required', new Enum(AWCCurrencyEnums::class)];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betType'] = ['nullable', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.betTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];

            } elseif ($message_data['action'] === AWCActionEnums::CANCEL_BET->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];

            } elseif ($message_data['action'] === AWCActionEnums::ADJUST_BET->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.adjustAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];

            } elseif ($message_data['action'] === AWCActionEnums::VOID_BET->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];
                $rules['message.txns.*.voidType'] = ['required', 'integer'];

            } elseif ($message_data['action'] === AWCActionEnums::UNVOID_BET->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];
                $rules['message.txns.*.voidType'] = ['required', 'integer'];

            } elseif ($message_data['action'] === AWCActionEnums::REFUND->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betType'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.winAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.turnover'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.betTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.refundPlatformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];

            } elseif ($message_data['action'] === AWCActionEnums::SETTLE->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betType'] = ['nullable', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.winAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.turnover'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.betTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];
                $rules['message.txns.*.txTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.settleType'] = ['required', 'string', Rule::in(AWCProvider::getSettleType())];
                $rules['message.txns.*.refundPlatformTxId'] = ['nullable', 'string', 'max:50'];

            } elseif ($message_data['action'] === AWCActionEnums::UNSETTLE->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];

            } elseif ($message_data['action'] === AWCActionEnums::VOID_SETTLE->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];
                $rules['message.txns.*.voidType'] = ['required', 'integer'];

            } elseif ($message_data['action'] === AWCActionEnums::UNVOID_SETTLE->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.voidType'] = ['required', 'integer'];

            } elseif ($message_data['action'] === AWCActionEnums::BET_N_SETTLE->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.currency'] = ['required', 'string', new Enum(AWCCurrencyEnums::class)];
                $rules['message.txns.*.betType'] = ['nullable', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.betTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.winAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.turnover'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];
                $rules['message.txns.*.txTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.requireAmount'] = ['required', 'decimal:0,4'];

            } elseif ($message_data['action'] === AWCActionEnums::CANCEL_BET_N_SETTLE->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];

            } elseif ($message_data['action'] === AWCActionEnums::FREE_SPIN->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.refPlatformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.winAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.turnover'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.betTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];

            } elseif ($message_data['action'] === AWCActionEnums::GIVE->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.txTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.amount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.currency'] = ['required', 'string', new Enum(AWCCurrencyEnums::class)];
                $rules['message.txns.*.promotionTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.promotionId'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.promotionTypeId'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];

            } elseif ($message_data['action'] === AWCActionEnums::RESETTLE->value) {
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.platformTxId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.userId'] = ['required', 'string'];
                $rules['message.txns.*.platform'] = ['required', 'string'];
                $rules['message.txns.*.gameType'] = ['required', 'string', 'max:10'];
                $rules['message.txns.*.gameCode'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameName'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betType'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.winAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.turnover'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.betTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.roundId'] = ['required', 'string', 'max:50'];
                $rules['message.txns.*.gameInfo'] = ['nullable', 'array'];
                $rules['message.txns.*.txTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns.*.settleType'] = ['required', 'string', Rule::in(AWCProvider::getSettleType())];
                $rules['message.txns.*.refundPlatformTxId'] = ['nullable', 'string', 'max:50'];

            }
        }

        return $rules;
    }

    // Override the failedValidation method
    protected function failedValidation(Validator $validator)
    {
        $result = AWCProvider::validationError($validator->errors()->first());

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_AWC)->first();

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
