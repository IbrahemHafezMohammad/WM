<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Constants\PaymentMethodHistoryConstants;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class PaymentMethodHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'isWithdraw',
        'changed_by',
        'transaction_id',
        'remark',
        'status',
        'previous_balance',
        'new_balance',
        'payment_method_id'
    ];

    protected $appends = [
        'status_name',
    ];

    public function getStatusNameAttribute()
    {
        return PaymentMethodHistoryConstants::getStatus($this->status);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'changed_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    //custom function
    public static function adjustBalance($amount, $isWithdraw, $remark, $old_balance, $changed_by_id, $payment_method)
    {
        self::create([
            'amount' => $amount,
            'isWithdraw' => $isWithdraw,
            'changed_by' => $changed_by_id,
            'remark' => $remark,
            'previous_balance' => $old_balance,
            'new_balance' => $payment_method->balance,
            'payment_method_id' => $payment_method->id,
            'status' => PaymentMethodHistoryConstants::STATUS_SUCCESS
        ]);
    }

    // custom function

    public static function getPaymentMethodsHistoriesWithRelations($searchParams)
    {
        $query = self::with(['paymentMethod', 'actor.user:id,user_name']);

        if (array_key_exists('from_date', $searchParams)) {
            $query->fromDate($searchParams['from_date']);
        }

        if (array_key_exists('to_date', $searchParams)) {
            $query->toDate($searchParams['to_date']);
        }

        if (array_key_exists('min_amount', $searchParams)) {
            $query->minAmount($searchParams['min_amount']);
        }

        if (array_key_exists('max_amount', $searchParams)) {
            $query->maxAmount($searchParams['max_amount']);
        }

        if (array_key_exists('payment_method_id', $searchParams)) {
            $query->paymentMethodId($searchParams['payment_method_id']);
        }

        if (array_key_exists('status', $searchParams)) {
            $query->status($searchParams['status']);
        }

        if (array_key_exists('type', $searchParams) && !is_null($searchParams['type'])) {
            $query->isWithdraw($searchParams['type']);
        }

        return $query;
    }

    public function scopeFromDate($query, $from_date)
    {
        $query->where('created_at', '>=', $from_date);
    }

    public function scopeToDate($query, $to_date)
    {
        $query->where('created_at', '<=', $to_date);
    }

    public function scopeMinAmount($query, $min_amount)
    {
        $query->where('amount', '>=', $min_amount);
    }

    public function scopeMaxAmount($query, $max_amount)
    {
        $query->where('amount', '<=', $max_amount);
    }

    public function scopePaymentMethodId($query, $payment_method_id)
    {
        $query->where('payment_method_id', $payment_method_id);
    }

    public function scopeStatus($query, $status)
    {
        $query->where('status', $status);
    }

    public function scopeIsWithdraw($query, $isWithdraw)
    {
        $query->where('isWithdraw', $isWithdraw);
    }

    public function scopeSuccessStatus($query)
    {
        $query->where('status', PaymentMethodHistoryConstants::STATUS_SUCCESS);
    }

    public static function getTotalProfitsPerDay($totalDepositsPerDay, $totalWithdrawsPerDay) {
        $totalProfitsPerDay = [];

        foreach ($totalDepositsPerDay as $date => $depositData) {
            $depositAmount = $depositData['total_deposit'] ?? 0;
            $withdrawAmount = $totalWithdrawsPerDay[$date]['total_withdraw'] ?? 0;

            $profit = $depositAmount - $withdrawAmount;
            $totalProfitsPerDay[$date] = $profit;
        }

        foreach ($totalWithdrawsPerDay as $date => $withdrawData) {
            if (!isset($totalProfitsPerDay[$date])) {
                $totalProfitsPerDay[$date] = 0 - ($withdrawData['total_withdraw'] ?? 0);
            }
        }

        // Sort the array by date
        ksort($totalProfitsPerDay);

        return $totalProfitsPerDay;
    }

    public static function transactionApproved(Transaction $transaction, PaymentMethod $payment_method, $previous_balance, $isWithdraw, string $remark = null)
    {   
        $changed_by = Auth::check() ? Auth::user()->admin : null;
        $changed_by=$changed_by->id ?? null;

        Log::info($changed_by);
        
        if($changed_by !== null) {
            Log::info('create payment history error transaction id :' . $transaction->id. '. change by is showing null'.$changed_by);

        }
        
        return self::create([
            'amount' => $transaction->amount,
            'isWithdraw' => $isWithdraw,
            'changed_by' =>$changed_by,
            'transaction_id' => $transaction->id,
            'remark' => $remark,
            'status' => PaymentMethodHistoryConstants::STATUS_SUCCESS,
            'payment_method_id' => $payment_method->id,
            'previous_balance' => $previous_balance,
            'new_balance' => $payment_method->balance
        ]);
    }

    public static function transactionFailed(Transaction $transaction, PaymentMethod $payment_method, $previous_balance, $isWithdraw, string $remark = null)
    {   
        $changed_by = Auth::check() ? Auth::user()->admin : null;
        $changed_by=$changed_by->id ?? null;

        Log::info($changed_by);
        
        if($changed_by !== null) {
            Log::info('create payment history error transaction id :' . $transaction->id. '. change by is showing null'.$changed_by);

        }

        log::info([
            'amount' => $transaction->amount,
            'isWithdraw' => $isWithdraw,
            'changed_by' =>$changed_by,
            'transaction_id' => $transaction->id,
            'remark' => $remark,
            'status' => PaymentMethodHistoryConstants::STATUS_FAILURE,
            'payment_method_id' => $payment_method->id,
            'previous_balance' => $previous_balance,
            'new_balance' => $payment_method->balance
        ]);

        return self::create([
            'amount' => $transaction->amount,
            'isWithdraw' => $isWithdraw,
            'changed_by' =>$changed_by,
            'transaction_id' => $transaction->id,
            'remark' => $remark,
            'status' => PaymentMethodHistoryConstants::STATUS_FAILURE,
            'payment_method_id' => $payment_method->id,
            'previous_balance' =>$payment_method->balance,
            'new_balance' => $payment_method->balance
        ]);
    }

    public static function getPaymentBankHistory($searchParams)
    {
        $query =  self::with([
            'transaction'=> function ($query) {
                $query->with('actionBy');
            },
            'paymentMethod' => function ($query) {
                $query->with('bankCode');
            }
        ])->select('id' , 'previous_balance' , 'amount' , 'isWithdraw', 'new_balance' ,'payment_method_id' , 'status', 'transaction_id' , 'remark' , 'created_at');

        
        if (array_key_exists('from_date', $searchParams)) {
            $query->fromDate($searchParams['from_date']);
        }

        if (array_key_exists('to_date', $searchParams)) {
            $query->toDate($searchParams['to_date']);
        }

        if (array_key_exists('min_amount', $searchParams)) {
            $query->minAmount($searchParams['min_amount']);
        }

        if (array_key_exists('max_amount', $searchParams)) {
            $query->maxAmount($searchParams['max_amount']);
        }

        if (array_key_exists('payment_method_id', $searchParams)) {
            $query->paymentMethodId($searchParams['payment_method_id']);
        }

        if (array_key_exists('status', $searchParams)) {
            $query->status($searchParams['status']);
        }

        if (array_key_exists('type', $searchParams) && !is_null($searchParams['type'])) {
            $query->isWithdraw($searchParams['type']);
        }
        return $query;
    }
}
