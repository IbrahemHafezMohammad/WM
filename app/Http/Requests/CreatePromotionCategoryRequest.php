<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Models\PromotionCategory;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Http\FormRequest;
use App\Constants\PromotionCategoryConstants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreatePromotionCategoryRequest extends FormRequest
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
            'en' => ['required', 'string'],
            'hi' => ['nullable', 'string'],
            'tl' => ['nullable', 'string'],
            'vn' => ['nullable', 'string'],
            'is_active' => 'boolean',
            'icon_image' => ['nullable', 'string', 'max:1000'],
            'icon_image_desktop' => ['nullable', 'string', 'max:1000'],
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
    
    public function getPromotionCategoryData()
    {
        $validated = $this->validated();

        $max = PromotionCategory::max('sort_order');

        $name = [
            'en' => $validated['en'],
            'hi' => $validated['hi'] ?? null,
            'tl' => $validated['tl'] ?? null,
            'vn' => $validated['vn'] ?? null,
        ];

        return [
            'name' => $name,
            'is_active' => $validated['is_active'] ?? true,
            'icon_image' => $validated['icon_image'] ?? null,
            'icon_image_desktop' => $validated['icon_image_desktop'] ?? null,
            'sort_order' => $max ? $max + 1 : 1
        ];
    } 
}
