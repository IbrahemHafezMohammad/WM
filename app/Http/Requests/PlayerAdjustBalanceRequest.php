<?php

namespace App\Http\Requests;

use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use App\Constants\PlayerBalanceHistoryConstants;

class PlayerAdjustBalanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Change Player Balance');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'decimal:0,2'],
            'remark' => ['nullable', 'string']
        ];
    }

    // get date before adjusting balance
    public function getPlayerBalanceHistoryData() {

        $validated =  $validated = $this->validated();

        $wallet = $this->player->wallet;

        $isWithdrawal = $wallet->balance > $validated['amount'];
        
        $diff = abs($wallet->balance - $validated['amount']);
        
        return [
            'amount' => $diff,
            'is_deduction' => $isWithdrawal,
            'action_by' => Auth::user()->id,
            'remark' => $validated['remark'] ?? null,
            'previous_balance' => $wallet->balance,
            'new_balance' => $validated['amount'],
            'currency' => $wallet->currency,
            'status' => PlayerBalanceHistoryConstants::STATUS_SUCCESS
        ];
    }
}
