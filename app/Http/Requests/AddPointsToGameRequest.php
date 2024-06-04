<?php

namespace App\Http\Requests;

use App\Constants\GameItemConstants;
use App\Constants\PlayerConstants;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPointsToGameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Modify Player Game Points');;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'points' => ['required', 'decimal:0,2'],
            'game_id' => ['required', 'integer', Rule::exists(GameItemConstants::TABLE_NAME, 'id')],
            'player_id' => ['required', 'integer', Rule::exists(PlayerConstants::TABLE_NAME, 'id')],
        ];
    }
}
