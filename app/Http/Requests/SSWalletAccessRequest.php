<?php

namespace App\Http\Requests;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\SSProvider\SSProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\Providers\SSProvider\Enums\SSActionsEnums;

class SSWalletAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function changeDataStructure()
    {
        $wallet_action = $this->route('wallet_action');

        if ($wallet_action == SSActionsEnums::CHECK_TRANSACTION) {

            if ($this->has('wagerid')) {

                $wagerid = $this->get('wagerid');

                if (!is_array($wagerid)) {

                    $wageridArray = array_map('trim', explode(',', $wagerid));

                    $this->merge(['wagerid' => $wageridArray]);
                }
            }
        }
    }

    protected function getValidatorInstance()
    {
        $this->changeDataStructure();
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

        if ($wallet_action == SSActionsEnums::PING) {

            $rules['type'] = ['required', 'int'];
        }

        if ($wallet_action == SSActionsEnums::GET_BALANCE) {

            $rules['acc'] = ['required', 'string', 'max:48'];
        }

        if ($wallet_action == SSActionsEnums::DEDUCT_BALANCE) {

            $rules['acc'] = ['required', 'string', 'max:48'];
            $rules['transid'] = ['required', 'string', 'max:48'];
            $rules['amt'] = ['required', 'decimal:0,2'];
        }

        if ($wallet_action == SSActionsEnums::ROLLBACK_TRANSACTION) {

            $rules['trxresult'] = ['required', 'array'];
            $rules['trxresult.*.refer_transid'] = ['required', 'string', 'max:48'];
            $rules['trxresult.*.result'] = ['required', 'boolean'];
            $rules['trxresult.*.remark'] = ['required', 'string', 'max:48'];
            $rules['trxresult.*.transid'] = ['required', 'string', 'max:48'];
        }

        if ($wallet_action == SSActionsEnums::CHECK_TRANSACTION) {

            $rules['transid'] = ['required', 'string', 'max:48'];
            $rules['type'] = ['required', 'string'];
            $rules['wagerid'] = ['required', 'array'];
            $rules['wagerid.*'] = ['required', 'string'];
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $result = SSProvider::validationError($validator->errors()->first());

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_SS)->first();

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
