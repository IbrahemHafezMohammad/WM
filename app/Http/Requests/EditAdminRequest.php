<?php

namespace App\Http\Requests;

use App\Rules\PhoneRegex;
use App\Rules\UserNameRegex;
use Illuminate\Validation\Rule;
use App\Constants\UserConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class EditAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Create Admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_name' => ['required', Rule::unique(UserConstants::TABLE_NAME, 'user_name')->ignore($this->admin->user->id), new UserNameRegex],
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', Rule::unique(UserConstants::TABLE_NAME, 'phone')->ignore($this->admin->user->id), new PhoneRegex],
        ];
    }

    public function getUserData()
    {
        $validated = $this->validated();
        return [
            'user_name' => $validated['user_name'],
            'phone' => $validated['phone'] ?? null,
            'name' => $validated['name'] ?? null
        ];
    }
}
