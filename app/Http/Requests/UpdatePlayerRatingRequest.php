<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Constants\PlayerRatingConstants;
use Illuminate\Validation\Rule;

class UpdatePlayerRatingRequest extends FormRequest
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
            'comment'=> [ 'nullable' ],
            'rating' => [ 'required', 'numeric', 'min:1', 'max:5'],
            'department'=> [ 'required' , Rule::in(array_keys(PlayerRatingConstants::getDepartments())) ],
        ];
    }

    public function getPlayerRatingData()
    {
     $validated = $this->validated();

        return [
            'comment' => $validated[ 'comment' ],
            'rating' => $validated[ 'rating' ],
            'department' => $validated[ 'department'],
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
