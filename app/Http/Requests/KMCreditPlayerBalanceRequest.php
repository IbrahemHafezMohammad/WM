<?php

namespace App\Http\Requests;

use App\Models\GameItem;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Validation\Rule;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Providers\KMProvider\KMProvider;
use Illuminate\Http\Exceptions\HttpResponseException;

class KMCreditPlayerBalanceRequest extends FormRequest
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
        return [
             // 'transactional' => ['required', 'boolean'],
             'transactions' => ['required', 'array'],
             'transactions.*.userid' => ['required', 'string', 'max:50'],
             'transactions.*.authtoken' => ['nullable', 'string', 'max:2000'],
             'transactions.*.brandcode' => ['required', 'string', 'max:20'],
             'transactions.*.amt' => ['required', 'decimal:0,6', 'min:0'],
             'transactions.*.cur' => ['required', 'string', 'between:3,8'],
             'transactions.*.ipaddress' => ['nullable', 'string', 'max:40'],
             'transactions.*.txtype' => ['required', 'integer'],
             'transactions.*.ptxid' => ['required', 'string', 'max:36'],
             'transactions.*.refptxid' => ['nullable', 'string', 'max:36'],
             'transactions.*.timestamp' => ['required', 'date_format:Y-m-d\TH:i:sP'],
             'transactions.*.platformtype' => ['required', 'integer', Rule::in(array_keys(KMProvider::getDevices()))],
             'transactions.*.gpcode' => ['required', 'string', 'max:50'],
             'transactions.*.gamecode' => ['required', 'string', 'max:50'],
             'transactions.*.gamename' => ['required', 'string', 'max:50'],
             'transactions.*.externalgameid' => ['required', 'string', 'max:50'],
             'transactions.*.roundid' => ['required', 'string', 'max:36'],
             'transactions.*.externalroundid' => ['required', 'string', 'max:64'],
             'transactions.*.senton' => ['required', 'date_format:Y-m-d\TH:i:sP'],
             'transactions.*.externalbetid' => ['nullable', 'string', 'max:64'],
             'transactions.*.isclosinground' => ['required', 'boolean'],
             'transactions.*.ggr' => ['required', 'decimal:0,6'],
             'transactions.*.turnover' => ['required', 'decimal:0,6', 'min:0'],
             'transactions.*.isbuyingame' => ['required', 'boolean'],
             'transactions.*.commission' => ['nullable', 'decimal:0,6'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $result = KMProvider::validationError($validator->errors()->first());

        $response = response()->json($result->response, $result->status_code);

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_KM)->first();

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
