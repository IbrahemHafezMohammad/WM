<?php

namespace App\Http\Requests;

use App\Constants\AgentConstants;
use App\Constants\UserConstants;
use App\Rules\AgentUniqueCodeRegex;
use App\Rules\PhoneRegex;
use App\Rules\UserNameRegex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentRequest extends FormRequest
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
            'user_name' => ['required', Rule::unique(UserConstants::TABLE_NAME)->ignore($this->agent->user->id), new UserNameRegex],
            'phone' => ['nullable', 'string', Rule::unique(UserConstants::TABLE_NAME,'phone')->ignore($this->agent->user->id)],
            'unique_code' => ['required', Rule::unique(AgentConstants::TABLE_NAME)->ignore($this->agent->id), new AgentUniqueCodeRegex],
            'senior_agent_id' => ['nullable', Rule::exists(AgentConstants::TABLE_NAME, 'id')]
        ];
    }

    public function getUserData()
    {
        $validated = $this->validated();
        return [
            'user_name' => $validated['user_name'],
            'phone' => $validated['phone'] ?? null,
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
}
