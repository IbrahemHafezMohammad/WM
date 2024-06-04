<?php

namespace App\Http\Requests;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Validation\Rules\Enum;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\EVOProvider\EVOProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\Providers\EVOProvider\Enums\EVOActionsEnums;
use App\Services\Providers\EVOProvider\Enums\EVOCurrencyEnums;

class EVOWalletAccessRequest extends FormRequest
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

        $rules = [
            'uuid' => ['required', 'string', 'max:255'],
            'userId' => ['required', 'string', 'max:255'],
            'sid' => ['required', 'string', 'max:255'],
        ];

        if ($wallet_action == EVOActionsEnums::BALANCE) {

            $rules['currency'] = ['required', new Enum(EVOCurrencyEnums::class)];
        }

        if (EVOActionsEnums::isWalletChangeAction($wallet_action)) {
            $rules['currency'] = ['required', new Enum(EVOCurrencyEnums::class)];
            $rules['game'] = ['required', 'array'];
            $rules['game.id'] = ['required', 'string', 'max:255'];
            $rules['game.type'] = ['required', 'string', 'max:255'];
            $rules['game.details'] = ['required', 'array'];
            $rules['game.details.table'] = ['required', 'array'];
            $rules['game.details.table.id'] = ['required', 'string', 'max:255'];
            $rules['game.details.table.vid'] = ['nullable', 'string', 'max:255'];
            $rules['transaction'] = ['required', 'array'];
            $rules['transaction.id'] = ['required', 'string', 'max:255'];
            $rules['transaction.refId'] = ['required', 'string', 'max:255'];
            $rules['transaction.amount'] = ['required', 'decimal:0,2'];
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $result = EVOProvider::validationError($validator->errors()->first());

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_EVO)->first();

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
