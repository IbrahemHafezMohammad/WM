<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\Player;
use Illuminate\Validation\Rule;
use App\Constants\UserConstants;
use App\Constants\PlayerConstants;
use App\Constants\BankCodeConstants;
use Illuminate\Support\Facades\Auth;
use App\Constants\PaymentCategoryConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserPaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Create User Payment Method');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'bank_code_id' => ['required', 'integer', Rule::exists(BankCodeConstants::TABLE_NAME, 'id')],
            'payment_category_id' => ['required', 'integer', Rule::exists(PaymentCategoryConstants::TABLE_NAME, 'id')],
            'account_number' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'bank_city' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'remark' => ['nullable', 'string', 'max:255'],
            'player_id' => ['required', 'integer', Rule::exists(PlayerConstants::TABLE_NAME, 'id')],
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

    public function getUserPaymentMethodData()
    {
        $validated = $this->validated();

        $player = Player::findOrFail($validated['player_id']);

        return [
            'bank_code_id' => $validated['bank_code_id'],
            'payment_category_id' => $validated['payment_category_id'],
            'account_number' => $validated['account_number'],
            'user_id' => $player->user->id,
            'account_name' => $validated['account_name'],
            'bank_city' => $validated['bank_city'] ?? null,
            'bank_branch' => $validated['bank_branch'] ?? null,
            'remark' => $validated['remark'] ?? null,
            'is_active' => true,
            'currency' => $player->wallet->currency
        ];
    }
}
