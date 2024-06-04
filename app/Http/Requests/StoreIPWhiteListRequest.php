<?php

namespace App\Http\Requests;

use App\Constants\GlobalConstants;
use App\Constants\IPWhitelistConstants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StoreIPWhiteListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('Create IP Whitelist');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'ip' => ['required', 'string', 'max:255', 'unique:whitelist_i_p_s'],
            'type' => ['required', 'integer', Rule::in(array_keys(IPWhitelistConstants::getTypes()))],
        ];
    }

    public function getIpWhiteListData(): array
    {
        $validated = $this->validated();
        return [
            'name' => $validated['name']??null,
            'ip' => $validated['ip']??null,
            'type' => $validated['type']??null,

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
