<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Constants\GameItemConstants;
use Illuminate\Support\Facades\Auth;
use App\Constants\GameCategoryConstants;
use App\Constants\GamePlatformConstants;
use Illuminate\Foundation\Http\FormRequest;

class ListGameItemsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('View Game Lists');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'game_category_id' => Rule::exists(GameCategoryConstants::TABLE_NAME, 'id'),
            'game_platform_id' => Rule::exists(GamePlatformConstants::TABLE_NAME, 'id'),
            'property' => ['integer', Rule::in(GameItemConstants::getProperties())],
            'name' => ['nullable', 'string'],
            'game_code' => ['nullable', 'string'],
        ];
    }
}
