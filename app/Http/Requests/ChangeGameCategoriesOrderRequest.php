<?php

namespace App\Http\Requests;

use App\Constants\GameCategoryConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChangeGameCategoriesOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Create Game Category');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'records' => ['required'],
            'records.*.id' => ['required', 'integer', Rule::exists(GameCategoryConstants::TABLE_NAME, 'id')],
            'records.*.sort_order' => ['required', 'integer']
        ];
    }
}
