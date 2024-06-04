<?php

namespace App\Http\Requests;

use App\Constants\PlayerConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminCreatePlayerNotificationRequest extends FormRequest
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
            'player_id' => ['required', 'integer', Rule::exists(PlayerConstants::TABLE_NAME, 'id')],
            'notification' => ['required', 'string']
        ];
    }

    public function getPlayerNotificationData()
    {

        $validated = $this->validated();

        return [
            'player_id' => $validated['player_id'],
            'notification' => $validated['notification'],
            'created_by' => Auth::user()->admin->id,
            'read' => false,
        ];
    }
}