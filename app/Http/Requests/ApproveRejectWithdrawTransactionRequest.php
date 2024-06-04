<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Constants\TransactionConstants;
use App\Constants\PaymentMethodConstants;
use Illuminate\Foundation\Http\FormRequest;

class ApproveRejectWithdrawTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $status = $this->transaction->status;
        $valid_status = !in_array($status, [TransactionConstants::STATUS_APPROVED, TransactionConstants::STATUS_REJECTED]);
        
        return (
            $this->transaction->isWithdraw
            && $valid_status
            && ($this->transaction->isWithdrawTransaction->risk_action_status == TransactionConstants::RISK_ACTION_APPROVED)
            && ($this->transaction->isWithdrawTransaction->fa_locked_by == auth()->user()->id)
            && Auth::user()->hasPermissionTo('Finance Approve/Reject Withdraw')
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
            'payment_method_id' => [
                Rule::requiredIf($this->status === TransactionConstants::STATUS_APPROVED),
                'integer',
                Rule::exists(PaymentMethodConstants::TABLE_NAME, 'id')->where('allow_withdraw', true)->where('currency', $this->transaction->currency)
            ],
            'remark' => ['nullable', 'string'],
            'customer_message' => ['nullable', 'string']
        ];
    }

    public function getTransactionData()
    {
        $validated = $this->validated();

        return [
            'status' => $validated['status'],
            'customer_message' => $validated['customer_message'] ?? null,
            'remark' => $validated['remark'] ?? null,
            'action_by' => Auth::user()->id,
            'action_time' => Carbon::now()->toDateTime()
        ];
    }
}


//'integer',
//