<?php

namespace App\Http\Requests;

use App\Constants\AgentConstants;
use App\Constants\UserConstants;
use App\Rules\AgentUniqueCodeRegex;
use App\Rules\PasswordRegex;
use App\Rules\PhoneRegex;
use App\Rules\UserNameRegex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAgentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', new PasswordRegex],
            'user_name' => ['required', Rule::unique(UserConstants::TABLE_NAME, 'user_name'), new UserNameRegex],
            'phone' => ['required', 'string', Rule::unique(UserConstants::TABLE_NAME, 'phone')],
            'unique_code' => ['required', Rule::unique(AgentConstants::TABLE_NAME, 'unique_code'), new AgentUniqueCodeRegex],
            'senior_agent_id' => ['nullable', Rule::exists(AgentConstants::TABLE_NAME, 'id')]
        ];
    }

    public function getUserData()
    {
        $validated = $this->validated();
        return [
            'user_name' => $validated['user_name'],
            'password' => $validated['password'],
            'phone' => $validated['phone'],
            'name' => $validated['name'],
        ];
    }

    public function getAgentData()
    {
        $validated = $this->validated();
        return [
            'senior_agent_id' => $validated['senior_agent_id'] ?? null,
            'unique_code' => $validated['unique_code']
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
