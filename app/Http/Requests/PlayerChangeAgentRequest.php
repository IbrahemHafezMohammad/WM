<?php

namespace App\Http\Requests;

use App\Constants\AgentConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PlayerChangeAgentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Change Player Agent');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'agent_id' => ['required', 'integer', Rule::exists(AgentConstants::TABLE_NAME, 'id')]
        ];
    }

    public function getAgentChangeHistoryData()
    {
        $validated = $this->validated();

        return [
            'player_id' => $this->player->id,
            'previous_agent_id' => $this->player->agent_id,
            'new_agent_id' => $validated['agent_id'],
            'change_by' => Auth::user()->admin->id
        ];
    }
}
