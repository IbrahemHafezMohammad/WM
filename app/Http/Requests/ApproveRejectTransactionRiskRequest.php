<?php

namespace App\Http\Requests;

use App\Constants\TransactionConstants;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ApproveRejectTransactionRiskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return(
            $this->transaction->isWithdraw
            && Auth::user()->can('Approve/Reject Risk')
            && ($this->transaction->isWithdrawTransaction->risk_locked_by === Auth::user()->id)
            && ($this->transaction->isWithdrawTransaction->risk_action_status === TransactionConstants::RISK_ACTION_PENDING)
        );

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'risk_action_status' => ['required', 'integer', Rule::in(array_keys(TransactionConstants::getRiskStatuses()))],
            'risk_action_note' => ['nullable', 'string']
        ];
    }

    public function getTransactionData()
    {
        $validated = $this->validated();

        return [
            'risk_action_status' => $validated['risk_action_status'],
            'risk_action_by' => Auth::user()->id,
            'risk_action_note' => $validated['risk_action_note'] ?? null
        ];
    }
}
