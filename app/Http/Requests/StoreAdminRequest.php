<?php

namespace App\Http\Requests;

use App\Rules\PhoneRegex;
use App\Rules\PasswordRegex;
use App\Rules\UserNameRegex;
use Illuminate\Validation\Rule;
use App\Constants\UserConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAdminRequest extends FormRequest
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
            'user_name' => ['required', Rule::unique(UserConstants::TABLE_NAME, 'user_name'), 'min:1'],
            'password' => ['required', 'string', new PasswordRegex],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', Rule::unique(UserConstants::TABLE_NAME, 'phone')],
            'remark' => ['nullable', 'string'],
        ];
    }

    public function getUserData()
    {
        $validated = $this->validated();
        return [
            'user_name' => $validated['user_name'],
            'password' => $validated['password'],
            'phone' => $validated['phone'],
            'name' => $validated['name'],
            'remark' => $validated['remark'] ?? null,
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
