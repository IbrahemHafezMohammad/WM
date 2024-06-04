<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Constants\PromotionConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Constants\PromotionPromotionCategoryConstants;

class ChangePromotionsOrderRequest extends FormRequest
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
            'records' => ['required'],
            'records.*.id' => ['required', 'integer', Rule::exists(PromotionPromotionCategoryConstants::TABLE_NAME, 'id')->where('promotion_category_id', $this->promotion_category->id)],
            'records.*.sort_order' => ['required', 'integer']
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status' => false,
            'message' => $validator->errors(),
        ], 422);

        throw new HttpResponseException($response);
    }
}
