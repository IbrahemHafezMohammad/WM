<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Constants\GameCategoryConstants;
use Illuminate\Foundation\Http\FormRequest;

class ListGameCategoriesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('View Game Categories');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'game_category_id' => ['integer', Rule::exists(GameCategoryConstants::TABLE_NAME, 'id')],
        ];
    }
}