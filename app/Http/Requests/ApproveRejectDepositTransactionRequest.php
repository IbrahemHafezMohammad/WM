<?php

namespace App\Http\Requests;

use App\Constants\TransactionConstants;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ApproveRejectDepositTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $status = $this->transaction->status;
        $valid_status = !in_array($status, [TransactionConstants::STATUS_APPROVED, TransactionConstants::STATUS_REJECTED]);

        return (
            (bool) !$this->transaction->isWithdraw
            && $valid_status
            && $this->transaction->payment_method_id
            && ($this->transaction->isDepositTransaction->fa_locked_by == auth()->user()->id)
            && Auth::user()->hasPermissionTo('Finance Approve/Reject Deposit')
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
            'status' => ['required', 'integer', Rule::in(array_keys(TransactionConstants::getStatuses()))],
            'remark' => ['nullable', 'string'],
            'customer_message' => ['nullable', 'string']
        ];
    }

    public function getTransactionData()
    {
        $validated = $this->validated();

        return [
            'customer_message' => $validated['customer_message'] ?? null,
            'remark' => $validated['remark'] ?? null,
            'action_by' => Auth::user()->id,
            'action_time' => Carbon::now()->toDateTime()
        ];
    }
}
