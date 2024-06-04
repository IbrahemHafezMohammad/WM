<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Constants\GameItemConstants;
use Illuminate\Foundation\Http\FormRequest;

class ChangeGameItemStatusRequest extends FormRequest
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
            'status' => ['required', 'integer', Rule::in(array_keys(GameItemConstants::getStatuses()))],
        ];
    }
}
