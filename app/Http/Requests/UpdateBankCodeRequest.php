<?php

namespace App\Http\Requests;

use App\Constants\PaymentCategoryConstants;
use Illuminate\Validation\Rule;
use App\Constants\GlobalConstants;
use App\Constants\BankCodeConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateBankCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Create Bank Code');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'integer', Rule::in(array_keys(BankCodeConstants::getCodes()))],
            'image' => ['required', 'string', 'max:255'],
            'public_name.en_public_name' => ['required', 'string', 'max:255'],
            'public_name.vn_public_name' => ['nullable', 'string', 'max:255'],
            'public_name.tl_public_name' => ['nullable', 'string', 'max:255'],
            'public_name.hi_public_name' => ['nullable', 'string', 'max:255'],
            'payment_category_id' => ['required', 'integer', Rule::exists(PaymentCategoryConstants::TABLE_NAME, 'id')],
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

    public function getBankCodeData()
    {
        $validated = $this->validated();

        return [
            'code' => $validated['code'],
            'image' => $validated['image'],
            'public_name' => $validated['public_name'],
            'display_for_players' => $this->bank_code->display_for_players,
            'payment_category_id' => $validated['payment_category_id'],
            'status' => $this->bank_code->status,
        ];
    }
}
