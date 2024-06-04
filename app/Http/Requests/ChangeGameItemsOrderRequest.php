<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Constants\GameItemConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Constants\GameItemGameCategoryConstants;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangeGameItemsOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Create Game Item');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'records' => ['required'],
            'records.*.id' => ['required', 'integer', Rule::exists(GameItemGameCategoryConstants::TABLE_NAME, 'game_item_id')->where('game_category_id', $this->game_category->id)],
            'records.*.sort_order' => ['required', 'integer']
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
