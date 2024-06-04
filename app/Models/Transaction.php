<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use App\Events\TransactionCreated;
use Illuminate\Support\Facades\Log;
use App\Constants\TransactionConstants;
use App\Events\TransactionChangeEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Events\TransactionFinanceStatusChange;
use App\Services\PaymentService\DTOs\DepositDTO;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isTrue;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'amount',
        'status',
        'processing_by',
        'action_by',
        'isWithdraw',
        'payment_method_id',
        'user_payment_method_id',
        'attachment_url',
        'remark',
        'customer_message',
        'action_time',
        'manual_approval',
        'currency'
    ];

    protected static function booted(): void
    {
        static::updated(function (Transaction $transaction) {

            if (array_key_exists('status', $transaction->getChanges())) {
                Log::info("broadcasting transaction change event");
                broadcast(new TransactionChangeEvent($transaction))->toOthers();
            }
        });
    }

    protected $appends = ['base_amount', 'currency_name', 'status_name'];

    public function getBaseAmountAttribute()
    {
        return $this->amount ? round($this->amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getCurrencyNameAttribute()
    {
        return GlobalConstants::getCurrency($this->currency);
    }

    public function getStatusNameAttribute()
    {
        return TransactionConstants::getStatus($this->status);
    }

    protected function attachmentUrl(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? Storage::url($value) : $value
        );
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function processingBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processing_by');
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }

    public function paymentMethod(): belongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function userPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(UserPaymentMethod::class, 'user_payment_method_id');
    }

    public function paymentMethodHistories(): HasMany
    {
        return $this->hasMany(PaymentMethodHistory::class);
    }

    public function playerBalanceHistories(): HasMany
    {
        return $this->hasMany(PlayerBalanceHistory::class);
    }
    public function isDepositTransaction(): HasOne
    {
        return $this->hasOne(Deposit::class);
    }
    public function isWithdrawTransaction(): HasOne
    {
        return $this->hasOne(Withdraw::class);
    }

    //custom function
    public static function getTransactionsWithRelations($searchParams)
    {
        $query = Transaction::with([
            'player.user:id,user_name,phone,profile_pic',
            'player.agent:id,unique_code',
            'paymentMethod',
            'userPaymentMethod',
            'actionBy:id,user_name',
            //            'player:id,agent_id,user_id,language_name',
        ]);
        $query->when((bool) $searchParams['isWithdraw'], function ($query) {
            return $query->with('IsWithdrawTransaction');
        });
        $query->when(!(bool) $searchParams['isWithdraw'], function ($query) {
            return $query->with('IsDepositTransaction');
        });
        if (array_key_exists('isWithdraw', $searchParams)) {
            $query->transactionType($searchParams['isWithdraw']);
        }
        if (array_key_exists('transaction_id', $searchParams)) {
            $query->transactionId($searchParams['transaction_id']);
        }
        if (array_key_exists('agent_code', $searchParams)) {
            $query->agetID($searchParams['agent_code']);
        }

        if (array_key_exists('status', $searchParams) && !is_null($searchParams['status']) && !empty($searchParams['status'])) {
            $query->transactionStatuses($searchParams['status']);
        }

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

        if (array_key_exists('user_name', $searchParams)) {
            $query->userName($searchParams['user_name']);
        }

        return $query;
    }

    public static function getTransactionsForPlayer($player_id, $searchParams)
    {

        $query = Transaction::where('player_id', $player_id);

        if (array_key_exists('isWithdraw', $searchParams)) {
            $query->transactionType($searchParams['isWithdraw']);
        }

        if (array_key_exists('status', $searchParams) && !is_null($searchParams['status']) && !empty($searchParams['status'])) {
            $query->transactionStatus($searchParams['status']);
        }

        if (array_key_exists('from_date', $searchParams)) {
            $query->fromDate($searchParams['from_date']);
        }

        if (array_key_exists('to_date', $searchParams)) {
            $query->toDate($searchParams['to_date']);
        }


        return $query;
    }

    public static function getTotalProfitsPerDay($totalDepositsPerDay, $totalWithdrawsPerDay)
    {
        $totalProfitsPerDay = [];

        $allDates = $totalDepositsPerDay->merge($totalWithdrawsPerDay)->keys()->unique();

        foreach ($allDates as $date) {
            $depositAmount = $totalDepositsPerDay->get($date, 0);
            $withdrawAmount = $totalWithdrawsPerDay->get($date, 0);

            $profit = $depositAmount - $withdrawAmount;
            $totalProfitsPerDay[$date] = $profit;
        }

        // Sort the array by date
        ksort($totalProfitsPerDay);

        return $totalProfitsPerDay;
    }

    public static function getNameOfMonths()
    {
        $currentDate = now();
        $startDate = $currentDate->copy()->subMonths(11)->startOfMonth();
        $months = collect(range(0, 11))
            ->map(function ($i) use ($startDate) {
                return $startDate->copy()->addMonths($i)->format('F');
            })
            ->toArray();

        return $months;
    }

    public static function getMonthlyTransactionsData()
    {
        return self::orderBy('created_at')
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->created_at->format('F');
            })
            ->map(function ($transactions) {
                return [
                    'monthly_withdraw_amount' => $transactions->where('isWithdraw', true)->sum('amount'),
                    'monthly_deposit_amount' => $transactions->where('isWithdraw', false)->sum('amount')
                ];
            });
    }

    public static function getDailyCurrentMonthTransactionsData()
    {
        $query = self::whereMonth('created_at', now()->month)
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->created_at->format('d');
            })
            ->map(function ($transactions) {
                return [
                    'daily_withdraw_amount' => $transactions->where('isWithdraw', true)->sum('amount'),
                    'daily_deposit_amount' => $transactions->where('isWithdraw', false)->sum('amount')
                ];
            });

        $transactions = array_fill_keys(range(1, now()->daysInMonth), [
            'daily_withdraw_amount' => 0,
            'daily_deposit_amount' => 0
        ]);

        foreach ($transactions as $day => &$transaction) {
            $dayKey = str_pad($day, 2, '0', STR_PAD_LEFT);
            if (isset($query[$dayKey])) {
                $transaction = $query[$dayKey];
            }
        }

        return $transactions;
    }

    //scopes

    public function scopeTransactionType($query, $isWithdraw)
    {
        $query->where('isWithdraw', $isWithdraw);
    }

    public function scopeIsWithdraw($query, $isWithdraw)
    {
        $query->where('isWithdraw', $isWithdraw);
    }

    public function scopeTransactionStatus($query, $status)
    {
        $query->where('status', $status);
    }

    public function scopeTransactionStatuses($query, array $status)
    {
        $query->whereIn('status', $status);
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
    public function scopeTransactionId($query, $id)
    {
        $query->where('id', $id);
    }

    public function scopeUserName($query, $user_name)
    {
        $query->whereHas('player.user', function (Builder $query) use ($user_name) {
            $query->where('user_name', 'like', '%' . $user_name . '%');
        });
    }

    public function scopeAgetID($query, $agentCode)
    {
        $query->whereHas('player.agent', function (Builder $query) use ($agentCode) {
            $query->where('unique_code', 'like', '%' . $agentCode . '%');
        });
    }

    public function scopeDepositPendingTransactions($query)
    {
        $query->where('isWithdraw', false)->where('status', TransactionConstants::STATUS_PENDING);
    }

    public function scopeWithdrawPendingTransactions($query)
    {
        $query->where('isWithdraw', true)->where('status', TransactionConstants::STATUS_PENDING);
    }

    public function scopePendingOrProcessing($query)
    {
        $query->whereIn('status', [
            TransactionConstants::STATUS_PENDING,
            TransactionConstants::STATUS_PROCESSING,
            TransactionConstants::STATUS_WAITING_FOR_PAYMENT,
            TransactionConstants::STATUS_PAYMENT_FAILED
        ]);
    }

    public function transactionLoadForAdminView()
    {
        return $this->load(['paymentMethod', 'userPaymentMethod']);
    }

    public function transactionLoadForPlayerView()
    {
        return $this->load([
            'paymentMethod:id,bank_code_id,account_name,account_number,public_name,currency,payment_code',
            'paymentMethod.bankCode:id,code,public_name',
            'userPaymentMethod:id,bank_code_id,account_name,account_number,currency',
            'isDepositTransaction:id,transaction_id,payment_link',
            'userPaymentMethod.bankCode:id,code,public_name'
        ]);
    }

    public function depositApproved(string $remark = null)
    {
        DB::beginTransaction();

        $payment_method = $this->paymentMethod;
        $payment_method->balance += $this->amount;
        $payment_method->save();

        if ($this->checkIffirstDeposit($this->player_id)) {
            $this->isDepositTransaction->update(['is_first' => true]);
        }

        $this->update([
            'status' => TransactionConstants::STATUS_APPROVED,
            'action_time' => now()->toDateTimeString(),
        ]);

        $player = $this->player;
        $locked_wallet = $player->wallet()->lockForUpdate()->first();
        $locked_wallet->baseCredit($this->base_amount);

        PaymentMethodHistory::transactionApproved($this, $payment_method, ($payment_method->balance - $this->amount), false, $remark);
        Log::info("payemnt method", [$payment_method]);
        PlayerBalanceHistory::transactionApproved($this, $locked_wallet, ($locked_wallet->balance - $this->amount), false, $remark);

        DB::commit();
    }



    public function withdrawApproved(PaymentMethod $payment_method = null, string $remark = null)
    {
        DB::beginTransaction();
        if ($payment_method) {
            $this->payment_method_id = $payment_method->id;
            $this->save();
        } else {
            $payment_method = $this->paymentMethod;
        }
        Log::info("Im here. approving now");

        $this->update([
            'status' => TransactionConstants::STATUS_APPROVED,
            'action_time' => now()->toDateTimeString(),
        ]);
        if ($this->firstWithdrawCheck($this->player_id)) {
            $this->isWithdrawTransaction->update(['is_first' => true]);
        };
        $payment_method->balance -= $this->amount;
        $payment_method->save();

        $player = $this->player;
        $locked_wallet = $player->wallet()->lockForUpdate()->first();
        $locked_wallet->removeLockedBalance($this->amount);
        // $locked_wallet->debit($this->amount); // no need to debit because we already debit and lock the balance when the player create a withdraw request

        PaymentMethodHistory::transactionApproved($this, $payment_method, $payment_method->balance + $this->amount, true, $remark);

        PlayerBalanceHistory::transactionApproved($this, $locked_wallet, $locked_wallet->balance + $this->amount, true, $remark);

        DB::commit();
    }

    public function withdrawRequestProcessing(PaymentMethod $payment_method, $reference_no = null)
    {
        $this->payment_method_id = $payment_method->id;
        $this->save();

        $this->update([
            'status' => TransactionConstants::STATUS_WAITING_FOR_PAYMENT,
            'action_time' => now()->toDateTimeString(),
        ]);
    }

    public function withdrawRequestFailed($failed_message = null, $reference_no = null)
    {

        $this->update([
            'status' => TransactionConstants::STATUS_PAYMENT_FAILED,
            'action_time' => now()->toDateTimeString(),
        ]);

        $this->isWithdrawTransaction()->update([
            'payment_remark' => $failed_message,
        ]);
    }

    // payment functions

    public function failedPayment(DepositDTO $deposit_dto)
    {
        $this->update([
            'status' => TransactionConstants::STATUS_PAYMENT_FAILED,
            'action_time' => now()->toDateTimeString(),
        ]);

        $this->isDepositTransaction()->update([
            'reference_no' => $deposit_dto->reference_no,
            'payment_remark' => $deposit_dto->message,
            'payment_info' => $deposit_dto->bank_info,
        ]);
    }

    public function successPayment(DepositDTO $deposit_dto)
    {
        $this->update([
            'status' => TransactionConstants::STATUS_WAITING_FOR_PAYMENT,
            'action_time' => now()->toDateTimeString(),
        ]);

        $this->isDepositTransaction()->update([
            'payment_link' => $deposit_dto->payment_url,
            'reference_no' => $deposit_dto->reference_no,
            'payment_remark' => $deposit_dto->message,
            'payment_info' => $deposit_dto->bank_info,
        ]);
    }

    public function depositFailed($failed_message = null,$status=null)
    {
        if($status==TransactionConstants::STATUS_REJECTED){
            $this->update([
                'status' => TransactionConstants::STATUS_REJECTED,
                'action_time' => now()->toDateTimeString(),
            ]);

        }else{
            
            $this->update([
                'status' => TransactionConstants::STATUS_PAYMENT_FAILED,
                'action_time' => now()->toDateTimeString(),
            ]);
        }
      

        $this->isDepositTransaction()->update([
            'payment_remark' => $failed_message,
        ]);

        $payment_method = $this->paymentMethod;
        $previous_balance = $payment_method->previous_balance;

        if (is_null($previous_balance)) {
            Log::error("Previous balance is null for payment method ID: " . $payment_method->id);
            $previous_balance = 0; // or set to a default value or handle it based on your business logic
        }
        PaymentMethodHistory::transactionFailed($this, $payment_method, $previous_balance, false, $failed_message);
        
    }

    public function pendingManualApprove()
    {
        $this->update([
            'status' => TransactionConstants::STATUS_PENDING,
            'action_time' => now()->toDateTimeString(),
        ]);
    }

    public function checkIffirstDeposit($player_id)
    {
        Transaction::where('player_id', $player_id)->where('isWithdraw', false)->where('status', TransactionConstants::STATUS_APPROVED)->get()->count() == 0 ? $isFirstDeposit = true : $isFirstDeposit = false;
        return $isFirstDeposit;
    }

    public function firstWithdrawCheck($player_id)
    {
        Transaction::where('player_id', $player_id)->where('isWithdraw', true)->where('status', TransactionConstants::STATUS_APPROVED)->get()->count() == 0 ? $isFirstWithdraw = true : $isFirstWithdraw = false;
        return $isFirstWithdraw;
    }

}
