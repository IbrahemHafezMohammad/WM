<?php

namespace App\Models;

use App\Constants\UserConstants;
use App\Constants\GlobalConstants;
use App\Constants\PlayerConstants;
use App\Constants\PlayerBalanceHistoryConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Database\Eloquent\Builder as EagerBuilder;

class PlayerBalanceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'amount',
        'previous_balance',
        'new_balance',
        'currency',
        'is_deduction',
        'status',
        'transaction_id',
        'game_transaction_history_id',
        'action_by',
        'remark',
    ];

    protected $appends = ['base_amount', 'currency_name'];

    public function getBaseAmountAttribute()
    {
        return $this->amount ? round($this->amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getCurrencyNameAttribute()
    {
        return GlobalConstants::getCurrency($this->currency);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }

    public function gameTransactionHistory(): BelongsTo
    {
        return $this->belongsTo(GameTransactionHistory::class);
    }

    //game logic

    public static function gameAction($player_id, $previous_balance, $currency)
    {
        return self::make([
            'player_id' => $player_id,
            'previous_balance' => $previous_balance,
            'currency' => $currency,
            'status' => PlayerBalanceHistoryConstants::STATUS_PROCESSING,
        ]);
    }

    public function gameActionFailed(string $remark = null, $game_transaction_history_id = null)
    {
        $this->status = PlayerBalanceHistoryConstants::STATUS_FAILURE;
        is_null($remark) ?: $this->remark = $remark;
        is_null($game_transaction_history_id) ?: $this->game_transaction_history_id = $game_transaction_history_id;
        $this->save();
    }

    public function gameActionSuccess(
        $amount,
        $is_deduction,
        $new_balance,
        $game_transaction_history_id = null,
        $remark = null
    ) {
        $this->amount = $amount;
        $this->is_deduction = $is_deduction;
        $this->new_balance = $new_balance;
        $this->game_transaction_history_id = $game_transaction_history_id;
        $this->remark = $remark;
        $this->status = PlayerBalanceHistoryConstants::STATUS_SUCCESS;
        $this->save();
    }

    public function gameActionPending(
        $amount,
        $is_deduction,
        $new_balance,
        $game_transaction_history_id = null,
        $remark = null
    ) {
        $this->amount = $amount;
        $this->is_deduction = $is_deduction;
        $this->new_balance = $new_balance;
        $this->game_transaction_history_id = $game_transaction_history_id;
        $this->remark = $remark;
        $this->status = PlayerBalanceHistoryConstants::STATUS_PENDING;
        $this->save();
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->save();
    }
    // custom function

    public static function getPlayerBalanceHistory($searchParams)
    {
        $query = self::with([
            'actionBy:id,user_name',
            'transaction:id,isWithdraw',
            'player' => function ($query) {
                $query->with('user:id,user_name')->select(['id', 'user_id']);
            }
        ]);

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

        if (array_key_exists('type', $searchParams) && !is_null($searchParams['type'])) {
            $query->isDeduction($searchParams['type']);
        }

        if (array_key_exists('user_name', $searchParams)) {
            $query->userNameForPlayerOrAdmin($searchParams['user_name']);
        }

        if (array_key_exists('ignore_test_account', $searchParams) && $searchParams['ignore_test_account']) {
            $query->ignoreTestAccount();
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

    public function scopeIsDeduction($query, $type)
    {
        $query->where('is_deduction', $type);
    }

    public function scopeUserNameForPlayerOrAdmin($query, $user_name)
    {
        $query->whereHas('actionBy', function (Builder $query) use ($user_name) {

            $query->where(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $user_name . '%');

        })->orWhereHas('player.user', function (Builder $query) use ($user_name) {

            $query->where(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $user_name . '%');

        });
    }

    public function scopeIgnoreTestAccount($query)
    {
        $query->whereHas('player', function (Builder $query) {

            $query->where(PlayerConstants::TABLE_NAME . '.type', PlayerConstants::TYPE_NORMAL);
        });
    }

    public static function transactionApproved(Transaction $transaction, Wallet $locked_wallet, $previous_balance, $is_deduction, string $remark = null)
    {
        return self::create([
            'player_id' => $locked_wallet->player_id,
            'amount' => $transaction->amount,
            'previous_balance' => $previous_balance,
            'new_balance' => $locked_wallet->balance,
            'currency' => $locked_wallet->currency,
            'is_deduction' => $is_deduction,
            'status' => PlayerBalanceHistoryConstants::STATUS_SUCCESS,
            'transaction_id' => $transaction->id,
            'action_by' => Auth::check() ? Auth::user()->id : null,
            'remark' => $remark,
        ]);
    }

    public static function pointSuccess(Wallet $wallet,  $points , $previous_balance , $is_deduction , string $remark , $game_transaction_history_id)
    {
        return self::create([
            'player_id' => $wallet->player_id,
            'previous_balance' => $previous_balance,
            'amount' => $points,
            'new_balance' => $wallet->balance,
            'currency' => $wallet->currency,
            'status' => PlayerBalanceHistoryConstants::STATUS_SUCCESS,
            'transaction_id' => null,
            'is_deduction' => $is_deduction,
            'action_by' => Auth::check() ? Auth::user()->id : null,
            'remark' => $remark,
            'game_transaction_history_id' => $game_transaction_history_id
        ]);
    }
}
