<?php

namespace App\Http\Requests;

use App\Constants\TransactionConstants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class withdrawFaLockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasPermissionTo('Finance Approve/Reject Withdraw');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_fa_locked' => 'required|boolean',
        ];
    }

    public function getWithdrawData()
    {
        $validated = $this->validated();

        $fa_locked_by =($validated['is_fa_locked']==TransactionConstants::FA_LOCKED)?Auth::id():null;
            return [
                'is_fa_locked' => $validated['is_fa_locked'],
                'fa_locked_by' => $fa_locked_by,
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
