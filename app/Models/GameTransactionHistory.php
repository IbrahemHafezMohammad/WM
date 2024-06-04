<?php

namespace App\Models;

use App\Constants\UserConstants;
use App\Constants\GlobalConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Constants\GameTransactionHistoryConstants;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Database\Eloquent\Builder as EagerBuilder;

class GameTransactionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'points',
        'before_points',
        'after_points',
        'game_item_id',
        'game_platform_id',
        'currency',
        'remark',
        'player_id',
        'wallet_id',
        'bet_id',
        'bet_round_id',
        'action_by',
        'status',
        'transaction_request_no',
        'game_transaction_no',
        'refer_to',
        'transaction_type',
        'is_withdraw',
        'reference_no'
    ];

    protected $appends = ['currency_name', 'transaction_type_name'];

    public function getCurrencyNameAttribute()
    {
        return GlobalConstants::getCurrency($this->currency);
    }

    public function getTransactionTypeNameAttribute()
    {
        return GameTransactionHistoryConstants::getTransactionType($this->transaction_type);
    }

    public function referForSuccessful(): HasOne
    {
        return $this->hasOne(GameTransactionHistory::class, 'refer_to')->where('status', GameTransactionHistoryConstants::STATUS_SUCCESS);
    }

    public function referForFailed(): HasOne
    {
        return $this->hasOne(GameTransactionHistory::class, 'refer_to')->where('status', GameTransactionHistoryConstants::STATUS_FAILURE);
    }

    public function referTo(): BelongsTo
    {
        return $this->belongsTo(GameTransactionHistory::class, 'refer_to');
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'action_by');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class, 'bet_id');
    }

    public function betRound(): BelongsTo
    {
        return $this->belongsTo(BetRound::class, 'bet_round_id');
    }

    public function playerBalanceHistory(): HasOne
    {
        return $this->hasOne(PlayerBalanceHistory::class);
    }

    public function gameItem(): BelongsTo
    {
        return $this->belongsTo(GameItem::class, 'game_item_id');
    }

    public function gamePlatform(): BelongsTo
    {
        return $this->belongsTo(GamePlatform::class, 'game_platform_id');
    }

    // game login logic
    public static function gameAction($before_points, $player_id, $currency, $wallet_id, $game_item_id, $reference_no, $transaction_request_no = null, $game_platform_id = null)
    {
        return self::make([
            'before_points' => $before_points,
            'player_id' => $player_id,
            'currency' => $currency,
            'wallet_id' => $wallet_id,
            'game_item_id' => $game_item_id,
            'game_platform_id' => $game_platform_id,
            'status' => GameTransactionHistoryConstants::STATUS_PROCESSING,
            'reference_no' => $reference_no,
            'transaction_request_no' => $transaction_request_no,
        ]);
    }

    public static function pointSuccess(Wallet $wallet , Player $player ,$points , $before_points, $game_item_id, $game_platform_id = null , $is_withdraw ,string $remark = null)
    {
        return self::create([
            'before_points' => $before_points,
            'points' => $points,
            'after_points' => $wallet->balance,
            'player_id' => $player->id,
            'currency' => $wallet->currency,
            'wallet_id' => $wallet->id,
            'game_item_id' => $game_item_id,
            'game_platform_id' => $game_platform_id,
            'status' => GameTransactionHistoryConstants::STATUS_SUCCESS,
            'transaction_type' => GameTransactionHistoryConstants::TRANSACTION_TYPE_ADJUST,
            'is_withdraw' => $is_withdraw,
            'remark' => $remark
        ]);
    }

    public function gameActionFailed(
        $transaction_type,
        $amount,
        $is_withdraw,
        $remark = null,
        $game_transaction_no = null,
        $bet_id = null,
        $refer_to = null,
        $bet_round_id = null,
    ) {
        $this->transaction_type = $transaction_type;
        $this->points = $amount;
        $this->is_withdraw = $is_withdraw;
        $this->remark = $remark;
        $this->game_transaction_no = $game_transaction_no;
        $this->bet_id = $bet_id;
        $this->refer_to = $refer_to;
        $this->bet_round_id = $bet_round_id;
        $this->status = GameTransactionHistoryConstants::STATUS_FAILURE;
        $this->save();
    }

    public function gameActionSuccess(
        $transaction_type,
        $amount,
        $is_withdraw,
        $after_points,
        $remark = null,
        $game_transaction_no = null,
        $bet_id = null,
        $refer_to = null,
        $bet_round_id = null,
    ) {

        $this->transaction_type = $transaction_type;
        $this->points = $amount;
        $this->is_withdraw = $is_withdraw;
        $this->after_points = $after_points;
        $this->remark = $remark;
        $this->game_transaction_no = $game_transaction_no;
        $this->bet_id = $bet_id;
        $this->refer_to = $refer_to;
        $this->bet_round_id = $bet_round_id;
        $this->status = GameTransactionHistoryConstants::STATUS_SUCCESS;
        $this->save();
    }

    public function gameActionPending(
        $transaction_type,
        $amount,
        $is_withdraw,
        $after_points,
        $remark = null,
        $game_transaction_no = null,
        $bet_id = null,
        $refer_to = null,
        $bet_round_id = null,
    ) {

        $this->transaction_type = $transaction_type;
        $this->points = $amount;
        $this->is_withdraw = $is_withdraw;
        $this->after_points = $after_points;
        $this->remark = $remark;
        $this->game_transaction_no = $game_transaction_no;
        $this->bet_id = $bet_id;
        $this->refer_to = $refer_to;
        $this->bet_round_id = $bet_round_id;
        $this->status = GameTransactionHistoryConstants::STATUS_PENDING;
        $this->save();
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->save();
    }
    // custom function

    public static function getGameTransactionHistoriesWithRelations($searchParams)
    {
        $query = self::with([
            'actionBy:id,user_name',
            'gameItem:id,name',
            'player' => function ($query) {
                $query->with('user:id,user_name')->select(['id', 'user_id']);
            },
        ]);

        if (array_key_exists('from_date', $searchParams)) {
            $query->fromDate($searchParams['from_date']);
        }

        if (array_key_exists('to_date', $searchParams)) {
            $query->toDate($searchParams['to_date']);
        }

        if (array_key_exists('min_points', $searchParams)) {
            $query->minPoints($searchParams['min_points']);
        }

        if (array_key_exists('max_points', $searchParams)) {
            $query->maxPoints($searchParams['max_points']);
        }

        if (array_key_exists('isWithdraw', $searchParams)) {
            $query->isWithdraw($searchParams['isWithdraw']);
        }

        if (array_key_exists('user_name', $searchParams)) {

            $query->userName($searchParams['user_name']);
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

    public function scopeMinPoints($query, $min_points)
    {
        $query->where('points', '>=', $min_points);
    }

    public function scopeMaxPoints($query, $max_points)
    {
        $query->where('points', '<=', $max_points);
    }

    public function scopeIsWithdraw($query, $isWithdraw)
    {
        $query->where('isWithdraw', $isWithdraw);
    }

    public function scopeUserName($query, $user_name)
    {
        $query->whereHas('player.user', function (Builder $query) use ($user_name) {

            $query->where(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $user_name . '%');
        });
    }

    public function scopePending($query)
    {
        $query->where('status', GameTransactionHistoryConstants::STATUS_PENDING);
    }

    public function scopeStatus($query, $status)
    {
        $query->where('status', $status);
    }

    public function scopeReferenceNo($query, $reference_no)
    {
        $query->where('reference_no', $reference_no);
    }

    public function scopeReferenceNoIn($query, $reference_nos)
    {
        $query->whereIn('reference_no', $reference_nos);
    }

    public function scopeGameTransactionNo($query, $game_transaction_no)
    {
        $query->where('game_transaction_no', $game_transaction_no);
    }

    public function scopeOrGameTransactionNo($query, $game_transaction_no)
    {
        $query->orWhere('game_transaction_no', $game_transaction_no);
    }

    public function scopeGameTransactionNoIn($query, $game_transaction_nos)
    {
        $query->whereIn('game_transaction_no', $game_transaction_nos);
    }

    public function scopeTransactionRequestNo($query, $transaction_request_no)
    {
        $query->where('transaction_request_no', $transaction_request_no);
    }

    public function scopeOrTransactionRequestNo($query, $transaction_request_no)
    {
        $query->orWhere('transaction_request_no', $transaction_request_no);
    }

    public function scopeTransactionRequestNoIn($query, $transaction_request_nos)
    {
        $query->whereIn('transaction_request_no', $transaction_request_nos);
    }

    public function scopeGameItemCode($query, $game_item_code)
    {
        $query->whereHas('gameItem', function (Builder $query) use ($game_item_code) {

            $query->where('game_id', $game_item_code);
        });
    }

    public function scopeGamePlatformId($query, $game_platform_id)
    {
        $query->where('game_platform_id', $game_platform_id);
    }

    public function scopeTypeIn($query, array $types)
    {
        $query->whereIn('transaction_type', $types);
    }

    public function scopePlayerId($query, $player_id)
    {
        $query->where('player_id', $player_id);
    }

    public function createGameTransactionHistory($points){

    }
}
