<?php

namespace App\Http\Requests;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\SABAProvider\SABAProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\Providers\SABAProvider\Enums\SABAActionEnums;
use App\Services\Providers\SABAProvider\Enums\SABACurrencyEnums;

class SABAWalletAccessRequest extends FormRequest
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
        $rules = [
            'message' => ['required', 'array'],
            'message.action' => ['required', new Enum(SABAActionEnums::class)],
        ];

        $message_data = $this->message;

        if (!is_null($message_data) && isset($message_data['action'])) {

            if ($message_data['action'] === SABAActionEnums::GET_BALANCE->value) {

                $rules['message.userId'] = ['required', 'string'];

            } elseif ($message_data['action'] === SABAActionEnums::PLACE_BET->value) {
                $rules['message.operationId'] = ['required', 'string', 'max:50'];
                $rules['message.userId'] = ['required', 'string'];
                $rules['message.currency'] = ['required', new Enum(SABACurrencyEnums::class)];
                $rules['message.matchId'] = ['required', 'integer'];
                $rules['message.homeId'] = ['required', 'integer'];
                $rules['message.awayId'] = ['required', 'integer'];
                $rules['message.homeName'] = ['required', 'string'];
                $rules['message.awayName'] = ['required', 'string'];
                $rules['message.kickOffTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.betTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.betAmount'] = ['required', 'decimal:0,4'];
                $rules['message.actualAmount'] = ['required', 'decimal:0,4'];
                $rules['message.sportType'] = ['required', 'integer'];
                $rules['message.sportTypeName'] = ['required', 'string'];
                $rules['message.betType'] = ['required', 'integer'];
                $rules['message.betTypeName'] = ['required', 'string'];
                $rules['message.oddsType'] = ['required', 'integer'];
                $rules['message.oddsId'] = ['required', 'integer'];
                $rules['message.odds'] = ['required', 'decimal:0,4'];
                $rules['message.betChoice'] = ['nullable', 'string'];
                $rules['message.betChoice_en'] = ['nullable', 'string'];
                $rules['message.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.leagueId'] = ['required', 'integer'];
                $rules['message.leagueName'] = ['required', 'string'];
                $rules['message.leagueName_en'] = ['required', 'string'];
                $rules['message.sportTypeName_en'] = ['required', 'string'];
                $rules['message.betTypeName_en'] = ['required', 'string'];
                $rules['message.homeName_en'] = ['required', 'string'];
                $rules['message.awayName_en'] = ['required', 'string'];
                $rules['message.IP'] = ['required', 'string'];
                $rules['message.isLive'] = ['required', 'boolean'];
                $rules['message.refId'] = ['required', 'string'];
                $rules['message.betFrom'] = ['required', 'string'];
                $rules['message.debitAmount'] = ['required', 'string'];

            } else if ($message_data['action'] === SABAActionEnums::CONFIRM_BET->value) {
                $rules['message.operationId'] = ['required', 'string', 'max:50'];
                $rules['message.userId'] = ['required', 'string'];
                $rules['message.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.transactionTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.refId'] = ['required', 'string'];
                $rules['message.txns.*.txId'] = ['required', 'string'];
                $rules['message.txns.*.licenseeTxId'] = ['required', 'string'];
                $rules['message.txns.*.odds'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.oddsType'] = ['required', 'integer'];
                $rules['message.txns.*.actualAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.isOddsChanged'] = ['required', 'boolean'];
                $rules['message.txns.*.creditAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.debitAmount'] = ['required', 'decimal:0,4'];

            } else if ($message_data['action'] === SABAActionEnums::CANCEL_BET->value) {
                $rules['message.operationId'] = ['required', 'string', 'max:50'];
                $rules['message.userId'] = ['required', 'string'];
                $rules['message.updateTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}\+\d{2}:\d{2}$/'];
                $rules['message.txns'] = ['required', 'array'];
                $rules['message.txns.*.refId'] = ['required', 'string'];
                $rules['message.txns.*.creditAmount'] = ['required', 'decimal:0,4'];
                $rules['message.txns.*.debitAmount'] = ['required', 'decimal:0,4'];
            }

        }

        return $rules;
    }


    // Override the failedValidation method
    protected function failedValidation(Validator $validator)
    {
        $result = SABAProvider::validationError($validator->errors()->first());

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_SABA)->first();

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
