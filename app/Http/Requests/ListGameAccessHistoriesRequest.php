<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Constants\GameCategoryConstants;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;

class ListGameAccessHistoriesRequest extends FormRequest
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
            'from_date' => 'date_format:Y-m-d H:i:s',
            'to_date' => 'date_format:Y-m-d H:i:s',
            'game_platform_id' => ['integer', Rule::exists(GamePlatformConstants::TABLE_NAME, 'id')],
            'game_name' => 'string',
            'game_category_id' => ['integer', Rule::exists(GameCategoryConstants::TABLE_NAME, 'id')],
            'player_name' => 'string',
        ];
    }
}
