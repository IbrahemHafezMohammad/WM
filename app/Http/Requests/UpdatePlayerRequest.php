<?php

namespace App\Http\Requests;

use App\Constants\PlayerConstants;
use App\Constants\UserConstants;
use App\Constants\GlobalConstants;
use App\Models\User;
use App\Rules\PhoneRegex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class UpdatePlayerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Create Player');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'gender' => ['integer', Rule::in(array_keys(UserConstants::getGenders()))],
            'type' => ['integer', Rule::in(array_keys(PlayerConstants::getTypes()))],
            'birthday' => ['nullable', 'date_format:Y-m-d'],
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', Rule::unique(UserConstants::TABLE_NAME, 'phone')->ignore($this->player->user->id), new PhoneRegex],
            'remark' => ['nullable', 'string'],
            'currency' => ['required', 'integer' , Rule::in(array_keys(GlobalConstants::getCurrencies()))]
        ];
    }

    public function getUserData() {

        $validated = $this->validated();
        return [
            'phone' => $validated['phone'],
            'name' => $validated['name'] ?? null,
            'remark' => $validated['remark'] ?? null,
            'gender' => $validated['gender'] ?? UserConstants::GENDER_UNKNOWN,
            'birthday' => $validated['birthday'] ?? null
        ];
    }

    public function getPlayerData() {

        $validated = $this->validated();
        return [
            'type' => $validated['type'] ?? PlayerConstants::TYPE_NORMAL,
        ];
    }

    public function getWalletData() {

        $validated = $this->validated();
        return [
            'currency' => $validated['currency']
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
