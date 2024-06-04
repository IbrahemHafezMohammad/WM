<?php

namespace App\Http\Requests;

use App\Models\Agent;
use App\Rules\PhoneRegex;
use App\Rules\PasswordRegex;
use App\Rules\UserNameRegex;
use Illuminate\Validation\Rule;
use App\Constants\UserConstants;
use App\Constants\AgentConstants;
use App\Constants\GlobalConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterPlayerRequest extends FormRequest
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
            'user_name' => ['required', 'string', Rule::unique(UserConstants::TABLE_NAME, 'user_name'), new UserNameRegex],
            'password' => ['required', 'string', new PasswordRegex],
            'phone' => ['required', 'string', Rule::unique(UserConstants::TABLE_NAME, 'phone'), new PhoneRegex],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'integer', Rule::in(array_keys(GlobalConstants::getCurrencies()))],
            'gender' => ['nullable', 'integer', Rule::in(array_keys(UserConstants::getGenders()))],
            'language' => ['nullable', 'integer', Rule::in(array_keys(GlobalConstants::getLanguages()))],
            'agent_id' => ['nullable', 'string', 'max:50']
        ];
    }

    public function getUserData()
    {

        $validated = $this->validated();
        return [
            'user_name' => $validated['user_name'],
            'password' => $validated['password'],
            'phone' => $validated['phone'],
            'gender' => $validated['gender'] ?? UserConstants::GENDER_UNKNOWN,
            'name' => $validated['name']
        ];
    }

    public function getPlayerData()
    {

        $validated = $this->validated();
        $agent = null;
        if( $validated['agent_id'] ?? false){
            $agent =  Agent::where('unique_code', $validated['agent_id'])->first();
        }

        return [
            'agent_id' => $agent?->id,
            'language' => $validated['language'] ?? GlobalConstants::LANG_EN,
        ];
    }

    public function getWalletData()
    {

        $validated = $this->validated();
        return [
            'currency' => $validated['currency'],
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
