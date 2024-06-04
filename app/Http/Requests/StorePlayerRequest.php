<?php

namespace App\Http\Requests;

use App\Constants\AgentConstants;
use App\Constants\GlobalConstants;
use App\Constants\PlayerConstants;
use App\Constants\UserConstants;
use App\Rules\PasswordRegex;
use App\Rules\PhoneRegex;
use App\Rules\UserNameRegex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePlayerRequest extends FormRequest
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
            'user_name' => ['required', Rule::unique(UserConstants::TABLE_NAME, 'user_name'), new UserNameRegex],
            'password' => ['required', 'string', new PasswordRegex],
            'phone' => ['required', 'string', Rule::unique(UserConstants::TABLE_NAME, 'phone'), new PhoneRegex],
            'gender' => ['nullable', 'integer', Rule::in(array_keys(UserConstants::getGenders()))],
            'agent_id' => ['nullable', Rule::exists(AgentConstants::TABLE_NAME, 'id')],
            'active' => 'boolean',
            'allow_withdraw' => 'boolean',
            'allow_betting' => 'boolean',
            'type' => ['integer', Rule::in(array_keys(PlayerConstants::getTypes()))],
            'currency' => ['required', 'integer', Rule::in(array_keys(GlobalConstants::getCurrencies()))],
            'language' => ['nullable', 'integer', Rule::in(array_keys(GlobalConstants::getLanguages()))],
            'birthday' => ['nullable', 'date_format:Y-m-d'],
            'name' => ['required', 'string', 'max:255'],
            'remark' => ['nullable', 'string'],
        ];
    }

    public function getUserData() {

        $validated = $this->validated();
        return [
            'user_name' => $validated['user_name'],
            'password' => $validated['password'],
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
            'agent_id' => $validated['agent_id'] ?? null,
            'active' => $validated['active'] ?? PlayerConstants::IS_ACTIVE,
            'type' => $validated['type'] ?? PlayerConstants::TYPE_NORMAL,
            'language' => $validated['language'] ?? GlobalConstants::LANG_HI,
            'allow_withdraw' => $validated['allow_withdraw'] ?? true,
            'allow_betting' => $validated['allow_betting'] ?? true
        ];
    }

    public function getWalletData() {
        
        $validated = $this->validated();
        return [
            'currency' => $validated['currency'],
        ];
    }
}
