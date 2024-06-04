<?php

namespace App\Models;

use App\Constants\TransactionConstants;
use App\Events\TransactionChangeEvent;
use App\Events\TransactionCreated;
use App\Events\TransactionFinanceStatusChange;
use App\Events\TransactionRiskStatusChange;
use App\Events\TransactionWithdrawLockChange;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Withdraw extends Model
{
    use HasFactory;
    protected $with = ['riskLockedBy:id,name','riskActionBy:id,user_name','faLockedBy:id,name'];
    protected $fillable = [
        'fa_locked_by',
        'is_fa_locked',
        'risk_locked_by',
        'is_risk_locked',
        'risk_action_status',
        'risk_action_by',
        'risk_action_note',
        'transaction_id',
        'reference_no',
        'is_first',
    ];

    protected $appends = [
        'risk_action_status_string',
    ];
    public function getRiskActionStatusStringAttribute(): string
    {
        return TransactionConstants::getRiskStatus($this->risk_action_status);
    }
    protected static function booted(): void
    {
        static::updated(function (Withdraw $isWithdraw) {
            if(array_key_exists('risk_locked_by', $isWithdraw->getChanges()) ||
                array_key_exists('fa_locked_by', $isWithdraw->getChanges()) ||
                array_key_exists('risk_action_status', $isWithdraw->getChanges()) ||
                array_key_exists('risk_action_by', $isWithdraw->getChanges())){
                    broadcast(new TransactionChangeEvent($isWithdraw->transaction))->toOthers();
            }
        });
    }

    // Relation RiskLockedBy
    public function riskLockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'risk_locked_by');
    }
    // Relation RiskActionBy
    public function riskActionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'risk_action_by');
    }


    // Relation FaLockedBy
    public function faLockedBy(): BelongsTo

    {
        return $this->belongsTo(User::class, 'fa_locked_by');
    }

    // FA lock function on model
    public function faLockAndUnlock($user_id,$is_fa_locked){
        $this->update([
            'fa_locked_by'=>$user_id,
            'is_fa_locked'=>$is_fa_locked
        ]);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}
