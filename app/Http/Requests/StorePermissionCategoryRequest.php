<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
            'hi' => ['required', 'string']
        ];
    }

    public function getPermissionCategoryData()
    {
        $validated = $this->validated();
        
        return [
            'name' => json_encode(['en' => $validated['en'], 'hi' => $validated['hi'] ?? null])
        ];
    }
}