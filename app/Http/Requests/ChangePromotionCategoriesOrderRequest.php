<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use App\Constants\PromotionCategoryConstants;

class ChangePromotionCategoriesOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Create Promotion Category');
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
            'records.*.id' => ['required', 'integer', Rule::exists(PromotionCategoryConstants::TABLE_NAME, 'id')],
            'records.*.sort_order' => ['required', 'integer']
        ];
    }
}
