<?php

namespace App\Http\Requests;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\CMDProvider\CMDProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\Providers\CMDProvider\Enums\CMDActionsEnums;
use App\Services\Providers\CMDProvider\Encryption\AesCbcEncryptor;

class CMDWalletAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function decryptAndDecodeMessageField()
    {
        $requested_currency = $this->route('currency'); // Access route parameter

        $wallet_action = $this->route('wallet_action');

        $credentials = CMDProvider::getCredential($requested_currency);

        $aes_encryptor = new AesCbcEncryptor($credentials['partner_key']);

        if ($this->has('balancePackage') && is_string($this->input('balancePackage'))) {

            $balance_package = $this->input('balancePackage');

            if ($wallet_action !== CMDActionsEnums::GET_BALANCE) {

                $balance_package = urldecode($balance_package);
            }

            $decrypted_data = $aes_encryptor->decrypt($balance_package);

            // Log::info("BALANCE PACKAGE DATA");
            // Log::info($balance_package);
            // Log::info("BALANCE PACKAGE DECRYPTED");
            // Log::info($decrypted_data);

            $decoded = json_decode($decrypted_data, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['balancePackage' => $decoded]);
            }
        }
    }

    protected function getValidatorInstance()
    {
        $this->decryptAndDecodeMessageField();
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

        if ($wallet_action == CMDActionsEnums::AUTH_CHECK) {

            $rules['token'] = ['required', 'string', 'max:50'];
            $rules['secret_key'] = ['required', 'string', 'max:256'];
        }

        if ($wallet_action == CMDActionsEnums::GET_BALANCE) {

            $rules['method'] = ['required', 'string', 'max:50', 'in:GetBalance']; // hard coded not need for the process after this check
            $rules['balancePackage'] = ['required', 'array'];
            $rules['balancePackage.ActionId'] = ['required', 'integer', Rule::in(CMDProvider::getBalanceActionIds())];
            $rules['balancePackage.SourceName'] = ['required', 'string', 'max:50'];
            $rules['packageId'] = ['required', 'string', 'max:50'];
            $rules['dateSent'] = ['required', 'integer'];
        }

        if ($wallet_action == CMDActionsEnums::DEDUCT_BALANCE) {

            $rules['method'] = ['required', 'string', 'max:50', 'in:DeductBalance']; // hard coded not need for the process after this check
            $rules['balancePackage'] = ['required', 'array'];
            $rules['balancePackage.ActionId'] = ['required', 'integer', Rule::in(CMDProvider::deductBalanceActionIds())];
            $rules['balancePackage.SourceName'] = ['required', 'string', 'max:50'];
            $rules['balancePackage.TransactionAmount'] = ['required', 'decimal:0,4'];
            $rules['balancePackage.ReferenceNo'] = ['required', 'string', 'max:50'];
            $rules['packageId'] = ['required', 'string', 'max:50'];
            $rules['dateSent'] = ['required', 'integer'];
        }

        if ($wallet_action == CMDActionsEnums::UPDATE_BALANCE) {

            $rules['method'] = ['required', 'string', 'max:50', 'in:UpdateBalance']; // hard coded not need for the process after this check
            $rules['balancePackage'] = ['required', 'array'];
            $rules['balancePackage.ActionId'] = ['required', 'integer', Rule::in(CMDProvider::updateBalanceActionIds())];
            $rules['balancePackage.MatchID'] = ['nullable', 'integer'];
            $rules['balancePackage.TicketDetails'] = ['required', 'array'];
            $rules['balancePackage.TicketDetails.*.SourceName'] = ['required', 'string', 'max:50'];
            $rules['balancePackage.TicketDetails.*.ReferenceNo'] = ['nullable', 'string', 'max:50'];
            $rules['balancePackage.TicketDetails.*.TransactionAmount'] = ['required', 'decimal:0,4'];
            $rules['packageId'] = ['required', 'string', 'max:50'];
            $rules['dateSent'] = ['required', 'integer'];
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $result = CMDProvider::validationError($validator->errors()->first());

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_CMD)->first();

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
