<?php

namespace App\Http\Requests;

use App\Constants\GameItemConstants;
use App\Constants\IPWhitelistConstants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class WhitelistIPListingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('View IP Whitelist');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'ip' => ['nullable','string'],
            'name' => ['nullable', 'string'],
            'type' => ['nullable', 'integer', Rule::in(array_keys(IPWhitelistConstants::getTypes()))],
        ];
    }

    public function getSearchData()
    {
        $validated = $this->validated();
        return [
            'ip' => $validated['ip']?? null,
            'name' => $validated['name'] ?? null,
            'type' => $validated['type']?? IPWhitelistConstants::TYPE_BO_API,
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
