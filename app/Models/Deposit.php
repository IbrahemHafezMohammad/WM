<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Events\TransactionRiskStatusChange;
use App\Events\TransactionChangeEvent;
use App\Events\TransactionFinanceStatusChange;
use App\Services\PaymentService\DTOs\DepositDTO;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deposit extends Model
{
    use HasFactory;
    protected $with = ['faLockedBy:id,name'];

    protected $fillable = [
        'transaction_id',
        'fa_locked_by',
        'is_fa_locked',
        'deposit_transaction_no',
        'reference_no',
        'payment_remark',
        'payment_info',
        'payment_link',
        'is_first'
    ];


    protected static function booted(): void
    {
        static::updated(function (Deposit $isDeposit) {

            if (array_key_exists('is_fa_locked', $isDeposit->getChanges())) {

                Log::info('transaction finance deposit updated'. $isDeposit->id);
                broadcast(new TransactionChangeEvent($isDeposit->transaction))->toOthers();
            }
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function faLockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fa_locked_by', 'id');
    }
}
