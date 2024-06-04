<?php

namespace App\Http\Requests;

use App\Constants\GameItemConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerGameWithdrawAllPointsRequest extends FormRequest
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
            'game_items' => ['required'],
            'game_items.*.game_id' => ['required', Rule::exists(GameItemConstants::TABLE_NAME, 'id')],
            'game_items.*.amount' => ['required', 'decimal:0,2']
        ];
    }
}
