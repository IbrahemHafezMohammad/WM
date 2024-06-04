<?php

namespace App\Http\Requests;

use App\Models\GameItem;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Validation\Rules\Enum;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\VIAProvider\VIAProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\Providers\VIAProvider\Enums\VIATransactionBehaviorEnums;

class VIAChangePlayerBalance extends FormRequest
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
            'vendorId' => ['required', 'string', 'max:255'],
            'vendorPlayerId' => ['required', 'string', 'max:255'],
            'transactionId' => ['required', 'string', 'max:255'],
            'orderId' => ['required', 'string', 'max:255'],
            'transactionAmount' => ['required', 'decimal:0,6'],
            // 'prize' => ['sometimes', 'decimal:0,6'],
            'transactionBehavior' => [new Enum(VIATransactionBehaviorEnums::class)],
        ];

        if ($this->has('tipOrder') && !is_null($this->tipOrder)) {

            $rules += [
                'tipOrder' => ['required', 'array'],
                // 'tipOrder.txnCode' => ['required', 'string', 'max:255'],
                'tipOrder.status' => ['required', 'string', 'max:255'],
                'tipOrder.vendorId' => ['required', 'string', 'max:255'],
                'tipOrder.currency' => ['required', 'string', 'max:255'],
                'tipOrder.playerId' => ['required', 'string', 'max:255'],
                'tipOrder.vendorPlayerId' => ['required', 'string', 'max:255'],
                'tipOrder.giftType' => ['required', 'string', 'max:255'],
                'tipOrder.giftAmount' => ['required', 'decimal:0,6'],
                'tipOrder.gameCode' => ['required', 'string', 'max:255'],
                'tipOrder.device' => ['required', 'string', 'max:255'],
                'tipOrder.tipTime' => ['required', 'integer'],
                'tipOrder.updateTime' => ['required', 'integer'],
                'tipOrder.giftName' => ['required', 'string', 'max:255'],
            ];

        } elseif ($this->has('betOrder') && !is_null($this->betOrder)) {

            $rules += [
                'betOrder' => ['required', 'array'],
                // 'betOrder.orderId' => ['required', 'string', 'max:255'],
                'betOrder.vendorId' => ['required', 'string', 'max:255'],
                'betOrder.playerId' => ['required', 'string', 'max:255'],
                'betOrder.vendorPlayerId' => ['required', 'string', 'max:255'],
                'betOrder.updateTime' => ['required', 'integer'],
                'betOrder.settleTime' => ['nullable', 'integer'],
                'betOrder.currency' => ['required', 'string', 'max:255'],
                'betOrder.bet' => ['required', 'decimal:0,6'],
                'betOrder.validBet' => ['required', 'decimal:0,6'],
                'betOrder.winloss' => ['required', 'decimal:0,6'],
                'betOrder.rebate' => ['required', 'decimal:0,6'],
                'betOrder.status' => ['required', 'string', 'max:255'],
                'betOrder.gameCode' => ['required', 'string', 'max:255'],
                'betOrder.device' => ['required', 'string', 'max:255'],
                'betOrder.betGameModes' => ['required', 'array'],
                'betOrder.betGameModes.*' => ['required', 'string'],
                'betOrder.drawId' => ['required', 'string'],
                'betOrder.tableId' => ['required', 'string'],
                'betOrder.gameShoe' => ['required', 'string'],
                'betOrder.gameRound' => ['required', 'string'],
                'betOrder.productType' => ['required', 'string'],
                'betOrder.betTime' => ['required', 'integer'],
                // 'betOrder.gameInfo' => ['sometimes', 'json'],
            ];
        }

        return $rules;
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
