<?php

namespace App\Http\Requests;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\Providers\PinnacleProvider\PinnacleProvider;
use App\Services\Providers\PinnacleProvider\Enums\PinnacleActionsEnums;
use App\Services\Providers\PinnacleProvider\Enums\PinnacleCurrencyEnums;

class PinnacleProviderWalletAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function getRouteParams()
    {
        $routeParams = $this->route()->parameters();

        $this->merge(['route_params' => $routeParams]);
    }

    protected function getValidatorInstance()
    {
        $this->getRouteParams();

        return parent::getValidatorInstance();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $routeName = $this->route()->getName();
        $rules = [];

        // Log ::info('in validation');
        switch ($routeName) {
            // case PinnacleActionsEnums::PING->value:
            //     $rules = [
            //         'Timestamp' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/'],
            //     ];
            //     break;

            case PinnacleActionsEnums::BALANCE->value:
                $rules = [
                    'route_params.currency' => ['required', 'string', new Enum(PinnacleCurrencyEnums::class)],
                    // Additional rules
                ];
                break;

            case PinnacleActionsEnums::WAGERING->value:
                $rules = [
                    'route_params.currency' => ['required', new Enum(PinnacleCurrencyEnums::class)],
                    'route_params.agentcode' => ['required', 'string'],
                    'route_params.usercode' => ['required', 'string'],
                    'route_params.requestid' => ['required', 'string'],
                    'Signature' => ['required', 'string'],
                    'Timestamp' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/'],
                    'Actions' => ['required', 'array'],
                    'Actions.*.Id' => ['required'],
                    'Actions.*.Name' => ['required', 'string', Rule::in(PinnacleProvider::transactionActions())],
                ];
                $actions = $this->input('Actions', []);
                // Log::info('PinnacleProviderWalletAccessRequest actions', $actions);
                foreach ($actions as $index => $action) {
                    $prefix = "Actions.$index.";

                    if ($action['Name'] === PinnacleProvider::TRANSACTION_ACTION_BETTED) {
                        $rules[$prefix . 'Transaction'] = ['required', 'array'];
                        $rules[$prefix . 'Transaction.TransactionId'] = ['required'];
                        $rules[$prefix . 'Transaction.ReferTransactionId'] = ['nullable'];
                        $rules[$prefix . 'Transaction.TransactionType'] = ['required', Rule::in([PinnacleProvider::TRANSACTION_TYPE_DEBIT, PinnacleProvider::TRANSACTION_TYPE_CREDIT])];
                        $rules[$prefix . 'Transaction.TransactionDate'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/'];
                        $rules[$prefix . 'Transaction.Amount'] = ['required', 'decimal:0,8'];
                        $rules[$prefix . 'PlayerInfo'] = ['required', 'array'];
                        $rules[$prefix . 'PlayerInfo.LoginId'] = ['required', 'string'];
                        $rules[$prefix . 'PlayerInfo.UserCode'] = ['required', 'string'];
                        $rules[$prefix . 'WagerInfo'] = ['required', 'array'];
                        $rules[$prefix . 'WagerInfo.WagerId'] = ['required'];
                        $rules[$prefix . 'WagerInfo.Type'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.BetType'] = ['required', 'integer'];
                        $rules[$prefix . 'WagerInfo.Odds'] = ['required'];
                        $rules[$prefix . 'WagerInfo.OddsFormat'] = ['required', 'integer'];
                        $rules[$prefix . 'WagerInfo.ToWin'] = ['required', 'decimal:0,8'];
                        $rules[$prefix . 'WagerInfo.ToRisk'] = ['required', 'decimal:0,8'];
                        $rules[$prefix . 'WagerInfo.PlayerIPAddress'] = ['required', 'string'];
                        $rules[$prefix . 'WagerInfo.Legs'] = ['nullable', 'array'];
                        $rules[$prefix . 'WagerInfo.WagerMasterId'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.WagerNum'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.RoundRobinOptions'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.Sport'] = ['nullable', 'string'];
                    } elseif ($action['Name'] === PinnacleProvider::TRANSACTION_ACTION_ACCEPTED) {
                        $rules[$prefix . 'Transaction'] = ['nullable', 'array'];
                        $rules[$prefix . 'Transaction.TransactionId'] = ['nullable'];
                        $rules[$prefix . 'Transaction.ReferTransactionId'] = ['nullable'];
                        $rules[$prefix . 'Transaction.TransactionType'] = ['nullable', Rule::in([PinnacleProvider::TRANSACTION_TYPE_DEBIT, PinnacleProvider::TRANSACTION_TYPE_CREDIT])];
                        $rules[$prefix . 'Transaction.TransactionDate'] = ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/'];
                        $rules[$prefix . 'Transaction.Amount'] = ['nullable', 'decimal:0,8'];
                        $rules[$prefix . 'PlayerInfo'] = ['required', 'array'];
                        $rules[$prefix . 'PlayerInfo.LoginId'] = ['required', 'string'];
                        $rules[$prefix . 'PlayerInfo.UserCode'] = ['required', 'string'];
                        $rules[$prefix . 'WagerInfo'] = ['required', 'array'];
                        $rules[$prefix . 'WagerInfo.WagerId'] = ['required'];
                        $rules[$prefix . 'WagerInfo.Type'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.BetType'] = ['required', 'integer'];
                        $rules[$prefix . 'WagerInfo.Odds'] = ['required'];
                        $rules[$prefix . 'WagerInfo.OddsFormat'] = ['required', 'integer'];
                        $rules[$prefix . 'WagerInfo.ToWin'] = ['required', 'decimal:0,8'];
                        $rules[$prefix . 'WagerInfo.ToRisk'] = ['required', 'decimal:0,8'];
                        $rules[$prefix . 'WagerInfo.Legs'] = ['nullable', 'array'];
                        $rules[$prefix . 'WagerInfo.WagerMasterId'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.WagerNum'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.RoundRobinOptions'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.Sport'] = ['nullable', 'string'];
                    } elseif ($action['Name'] === PinnacleProvider::TRANSACTION_ACTION_SETTLED) {

                        $rules[$prefix . 'Transaction'] = ['nullable', 'array'];
                        $rules[$prefix . 'Transaction.TransactionId'] = ['nullable'];
                        $rules[$prefix . 'Transaction.ReferTransactionId'] = ['nullable'];
                        $rules[$prefix . 'Transaction.TransactionType'] = ['nullable', Rule::in([PinnacleProvider::TRANSACTION_TYPE_DEBIT, PinnacleProvider::TRANSACTION_TYPE_CREDIT])];
                        $rules[$prefix . 'Transaction.TransactionDate'] = ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/'];
                        $rules[$prefix . 'Transaction.Amount'] = ['nullable', 'decimal:0,8'];
                        $rules[$prefix . 'PlayerInfo'] = ['required', 'array'];
                        $rules[$prefix . 'PlayerInfo.LoginId'] = ['required', 'string'];
                        $rules[$prefix . 'PlayerInfo.UserCode'] = ['required', 'string'];
                        $rules[$prefix . 'WagerInfo'] = ['required', 'array'];
                        $rules[$prefix . 'WagerInfo.WagerId'] = ['required'];
                        $rules[$prefix . 'WagerInfo.Type'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.ProfitAndLoss'] = ['required', 'decimal:0,8'];
                        $rules[$prefix . 'WagerInfo.Outcome'] = ['required', 'string'];
                        $rules[$prefix . 'WagerInfo.BetType'] = ['required', 'integer'];
                        $rules[$prefix . 'WagerInfo.SettlementTime'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/'];
                        $rules[$prefix . 'WagerInfo.Odds'] = ['required'];
                        $rules[$prefix . 'WagerInfo.OddsFormat'] = ['required', 'integer'];
                        $rules[$prefix . 'WagerInfo.ToWin'] = ['required', 'decimal:0,8'];
                        $rules[$prefix . 'WagerInfo.ToRisk'] = ['required', 'decimal:0,8'];
                        $rules[$prefix . 'WagerInfo.Legs'] = ['nullable', 'array'];
                        $rules[$prefix . 'WagerInfo.WagerMasterId'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.WagerNum'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.RoundRobinOptions'] = ['nullable'];
                        $rules[$prefix . 'WagerInfo.Sport'] = ['nullable', 'string'];
                    } elseif (
                        $action['Name'] === PinnacleProvider::TRANSACTION_ACTION_REJECTED ||
                        $action['Name'] === PinnacleProvider::TRANSACTION_ACTION_ROLLBACKED ||
                        $action['Name'] === PinnacleProvider::TRANSACTION_ACTION_CANCELLED

                    ) {

                        $rules[$prefix . 'Transaction'] = ['required', 'array'];
                        $rules[$prefix . 'Transaction.TransactionId'] = ['required'];
                        $rules[$prefix . 'Transaction.ReferTransactionId'] = ['nullable'];
                        $rules[$prefix . 'Transaction.TransactionType'] = ['required', Rule::in([PinnacleProvider::TRANSACTION_TYPE_DEBIT, PinnacleProvider::TRANSACTION_TYPE_CREDIT])];
                        $rules[$prefix . 'Transaction.TransactionDate'] = ['required', 'regex:/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/'];
                        $rules[$prefix . 'Transaction.Amount'] = ['required', 'decimal:0,8'];
                        $rules[$prefix . 'PlayerInfo'] = ['required', 'array'];
                        $rules[$prefix . 'PlayerInfo.LoginId'] = ['required', 'string'];
                        $rules[$prefix . 'PlayerInfo.UserCode'] = ['required', 'string'];
                        $rules[$prefix . 'WagerInfo'] = ['required', 'array'];
                        $rules[$prefix . 'WagerInfo.WagerId'] = ['required'];
                    } elseif ($action['Name'] === PinnacleProvider::TRANSACTION_ACTION_UNSETTLED) {

                        $rules[$prefix . 'Transaction'] = ['nullable', 'array'];
                        $rules[$prefix . 'Transaction.TransactionId'] = ['nullable'];
                        $rules[$prefix . 'Transaction.ReferTransactionId'] = ['nullable'];
                        $rules[$prefix . 'Transaction.TransactionType'] = ['nullable', Rule::in([PinnacleProvider::TRANSACTION_TYPE_DEBIT, PinnacleProvider::TRANSACTION_TYPE_CREDIT])];
                        $rules[$prefix . 'Transaction.TransactionDate'] = ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/'];
                        $rules[$prefix . 'Transaction.Amount'] = ['nullable', 'decimal:0,8'];
                        $rules[$prefix . 'PlayerInfo'] = ['required', 'array'];
                        $rules[$prefix . 'PlayerInfo.LoginId'] = ['required', 'string'];
                        $rules[$prefix . 'PlayerInfo.UserCode'] = ['required', 'string'];
                        $rules[$prefix . 'WagerInfo'] = ['required', 'array'];
                        $rules[$prefix . 'WagerInfo.WagerId'] = ['required'];
                    }
                }

                break;
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $result = PinnacleProvider::validationError($validator->errors()->first());

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_PINNACLE)->first();

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
