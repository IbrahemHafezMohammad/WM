<?php

namespace App\Http\Requests;

use App\Models\Promotion;
use Illuminate\Validation\Rule;
use App\Constants\GlobalConstants;
use App\Constants\PromotionConstants;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Http\FormRequest;
use App\Constants\PromotionCategoryConstants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePromotionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'body' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'end_date' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'promotion_category_ids' => ['required', 'array'],
            'promotion_category_ids.*id' => ['required', Rule::exists(PromotionCategoryConstants::TABLE_NAME, 'id')],
            'country' => ['required', 'integer', Rule::in(array_keys(GlobalConstants::getCountries()))],
            'image' => ['required', 'string', 'max:1000'],
            'desktop_image' => ['nullable', 'string', 'max:1000'],
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
    
    public function getPromotionData()
    {
        $validated = $this->validated();
        
        return [
            'title' => $validated['title'],
            'status' => $validated['status'],
            'country' => $validated['country'],
            'image' => $validated['image'],
            'desktop_image' => $validated['desktop_image'] ?? null,
            'body' => $validated['body'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null
        ];
    }
}