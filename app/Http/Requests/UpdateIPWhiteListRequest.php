<?php

namespace App\Http\Requests;

use App\Constants\IPWhitelistConstants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
//use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UpdateIPWhiteListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('Update IP Whitelist');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    //make this rule funcction to validate the request

    public function rules(): array
    {
        $whitelistipId = $this->whitelist_ip;
        Log::info('the ip $whitelistipId');
        Log::info($whitelistipId);
        Log::info(json_encode($this->request->all()));
        Log::info('$whitelistipId');
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'ip' => ['required','string','max:255',
                Rule::unique('whitelist_i_p_s', 'ip')->ignore($this->whitelist_ip->id),],
            'type' => ['required','integer', Rule::in(array_keys(IPWhitelistConstants::getTypes()))],
        ];
    }

    public function getIpWhiteListData()
    {
        $validated = $this->validated();
        return [
            'name' => $validated['name'],
            'ip' => $validated['ip'],
            'type' => $validated['type'],
        ];
    }
      protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status' => false,
            'message' => $validator->errors(),
        ], 422);

        throw new HttpResponseException($response);
    }
}
