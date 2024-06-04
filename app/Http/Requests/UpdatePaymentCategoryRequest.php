<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
use App\Constants\PaymentCategoryConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Services\PaymentService\PaymentServiceEnum;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePaymentCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Update Payment Category');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'array'],
            'icon.en_icon' => [ 'nullable','string', 'max:255' ],
            'icon.vn_icon' => [ 'nullable','string', 'max:255' ],
            'icon.tl_icon' => [ 'nullable','string', 'max:255' ],
            'icon.hi_icon' => [ 'nullable','string', 'max:255' ],
            'public_name' => ['required', 'array'],
            'public_name.en_public_name' => ['required', 'string', 'max:255'],
            'public_name.vn_public_name' => ['nullable', 'string', 'max:255'],
            'public_name.tl_public_name' => ['nullable', 'string', 'max:255'],
            'public_name.hi_public_name' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['required', 'boolean'],
        ];
    }

    public function getPaymentCategoryData()
    {
        $validated = $this->validated();

        $icons= [];
        if(isset($validated['icon']) && is_array($validated['icon'])){
            $icons = [
                'en_icon' =>  isset($validated['icon']['en_icon']) ? $validated['icon']['en_icon'] : null,
                'vn_icon' => isset($validated['icon']['vn_icon']) ? $validated['icon']['vn_icon'] : null,
                'tl_icon' => isset($validated['icon']['tl_icon']) ? $validated['icon']['tl_icon'] : null,
                'hi_icon' => isset($validated['icon']['hi_icon']) ? $validated['icon']['hi_icon'] : null,
            ];
        }

        $public_names = [
            'en_public_name' => isset($validated['public_name']['en_public_name']) ? $validated['public_name']['en_public_name'] : null,
            'vn_public_name' => isset($validated['public_name']['vn_public_name']) ? $validated['public_name']['vn_public_name'] : null,
            'tl_public_name' => isset($validated['public_name']['tl_public_name']) ? $validated['public_name']['tl_public_name'] : null,
            'hi_public_name' => isset($validated['public_name']['hi_public_name']) ? $validated['public_name']['hi_public_name'] : null,
        ];

        return [
            'name' => $validated['name'],
            'icon' => $icons,
            'public_name' => $public_names,
            'is_enabled' => $validated['is_enabled'],
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


