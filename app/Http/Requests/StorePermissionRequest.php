<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use App\Constants\PermissionCategoryConstants;

class StorePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Add Permission');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', Rule::unique('permissions', 'name')],
            'en' => ['required', 'string'],
            'hi' => ['required', 'string'],
            'permission_category_id' => ['required', 'integer', Rule::exists(PermissionCategoryConstants::TABLE_NAME, 'id')]
        ];
    }

    public function getPermissionData()
    {
        $validated = $this->validated();
        
        return [
            'name' => $validated['name'],
            'label' => json_encode(['en' => $validated['en'], 'hi' => $validated['hi'] ?? null]),
            'permission_category_id' => $validated['permission_category_id']
        ];
    }
}