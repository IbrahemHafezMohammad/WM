<?php

namespace App\Models;

use App\Constants\BetConstants;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Constants\GameTransactionHistoryConstants;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Services\Providers\CMDProvider\CMDProvider;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bet extends Model
{
    use HasFactory;

    protected $fillable = [
        'bet_round_id',
        'bet_round_reference',
        'game_item_id',
        'game_code',
        'bet_reference',
        'bet_on',
        'closed_on',
        'status',
        'bet_amount',
        'valid_bet',
        'turnover',
        'win_amount',
        'win_loss',
        'rebate',
        'currency',
        'odds',
        'comm',
        'lifecycle',
        'game_info'
    ];

    protected $casts = [
        'lifecycle' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($bet) {
            $original = $bet->getOriginal();
            // Log::info('getOriginal : ' . json_encode($original));
            $changes = $bet->getDirty();
            // Log::info('getDirty : ' . json_encode($changes));

            $updateRecord = [];
            foreach ($changes as $key => $value) {

                if (!array_key_exists($key, $original)) {

                    $updateRecord[$key] = null;
                } elseif ($original[$key] != $value) { // Only track actual changes

                    $updateRecord[$key] = $original[$key];
                }
            }

            if (!empty($updateRecord)) {
                $updateRecord['created_at'] = $original['updated_at'];
                $currentLifecycle = $bet->lifecycle ?? [];
                $nextIndex = count($currentLifecycle) + 1;
                $currentLifecycle[$nextIndex] = $updateRecord;
                $bet->lifecycle = $currentLifecycle;
            }
        });
    }

    // if possible consider caching closed bets for performance (only closed bets don't cache and forget cache for all records that causes performance issues)
    protected $appends = ['currency_name', 'status_name', 'base_bet_amount', 'base_valid_bet', 'base_turnover', 'base_win_amount', 'base_rebate', 'base_win_loss'];

    public function getCurrencyNameAttribute()
    {
        return GlobalConstants::getCurrency($this->currency);
    }

    public function getStatusNameAttribute()
    {
        return BetConstants::getStatus($this->status);
    }

    public function getBaseBetAmountAttribute()
    {
        return $this->bet_amount ? round($this->bet_amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getBaseValidBetAttribute()
    {
        return $this->valid_bet ? round($this->valid_bet * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getBaseTurnoverAttribute()
    {
        return $this->turnover ? round($this->turnover * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getBaseWinAmountAttribute()
    {
        return $this->win_amount ? round($this->win_amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getBaseWinLossAttribute()
    {
        return $this->win_loss ? round($this->win_loss * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getBaseRebateAttribute()
    {
        return $this->rebate ? round($this->rebate * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    // relations

    public function betRound(): BelongsTo
    {
        return $this->belongsTo(BetRound::class);
    }

    public function gameItem(): BelongsTo
    {
        return $this->belongsTo(GameItem::class);
    }

    public function gameTransactionHistories(): HasMany
    {
        return $this->hasMany(GameTransactionHistory::class, 'bet_id');
    }

    public function latestGameTransactionHistory(): HasOne
    {
        return $this->hasOne(GameTransactionHistory::class, 'bet_id')->latestOfMany();
    }

    public function latestSuccessfulGameTransactionHistory(): HasOne
    {
        return $this->hasOne(GameTransactionHistory::class, 'bet_id')->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->latestOfMany();
    }

    public function latestFailedGameTransactionHistory(): HasOne
    {
        return $this->hasOne(GameTransactionHistory::class, 'bet_id')->status(GameTransactionHistoryConstants::STATUS_FAILURE)->latestOfMany();
    }

    // custom function

    public static function place(
        $bet_amount,
        $bet_round_reference,
        $bet_reference,
        $bet_round_id,
        $game_item_id,
        $bet_on,
        $currency,
        $odds = null,
        $rebate = null,
        $comm = null,
        $game_code = null,
        $game_info = null
    ) {
        return self::create([
            'bet_amount' => $bet_amount,
            'valid_bet' => $bet_amount,
            'turnover' => $bet_amount,
            'bet_round_id' => $bet_round_id,
            'game_item_id' => $game_item_id,
            'game_code' => $game_code,
            'bet_round_reference' => $bet_round_reference,
            'bet_reference' => $bet_reference,
            'bet_on' => $bet_on,
            'currency' => $currency,
            'odds' => $odds,
            'rebate' => $rebate,
            'comm' => $comm,
            'status' => BetConstants::STATUS_UNSETTLED,
            'game_info' => $game_info,
            'win_amount' => null
        ]);
    }

    public function adjust($bet_amount = null, $valid_bet = null, $turnover = null, $odds = null, $rebate = null, $comm = null, $bet_round_reference = null, $game_info = null)
    {
        is_null($bet_amount) ?: $this->bet_amount = $bet_amount;
        is_null($valid_bet) ?: $this->valid_bet = $valid_bet;
        is_null($turnover) ?: $this->turnover = $turnover;
        is_null($odds) ?: $this->odds = $odds;
        is_null($rebate) ?: $this->rebate = $rebate;
        is_null($comm) ?: $this->comm = $comm;
        is_null($bet_round_reference) ?: $this->bet_round_reference = $bet_round_reference;
        is_null($game_info) ?: $this->game_info = $game_info;
        $this->status = BetConstants::STATUS_UNSETTLED;
        $this->save();
    }

    public function accept(array $fields)
    {
        isset($fields['bet_amount']) ? $this->bet_amount = $fields['bet_amount'] : null;
        isset($fields['valid_bet']) ? $this->valid_bet = $fields['valid_bet'] : null;
        isset($fields['turnover']) ? $this->turnover = $fields['turnover'] : null;
        isset($fields['odds']) ? $this->odds = $fields['odds'] : null;
        isset($fields['rebate']) ? $this->rebate = $fields['rebate'] : null;
        isset($fields['comm']) ? $this->comm = $fields['comm'] : null;
        isset($fields['bet_round_reference']) ? $this->bet_round_reference = $fields['bet_round_reference'] : null;
        isset($fields['game_info']) ? $this->game_info = $fields['game_info'] : null;
        $this->status = BetConstants::STATUS_ACCEPTED;
        $this->save();
    }

    public function refund($win_amount, $closed_on, $rebate = null, $comm = null, $valid_bet = null, $turnover = null)
    {
        $this->win_amount = $win_amount;
        $this->closed_on = $closed_on;
        is_null($rebate) ?: $this->rebate = $rebate;
        is_null($comm) ?: $this->comm = $comm;
        is_null($valid_bet) ?: $this->valid_bet = $valid_bet;
        is_null($turnover) ?: $this->turnover = $turnover;
        $this->save();
    }

    public function settle($win_amount, $closed_on, $rebate = null, $comm = null, $valid_bet = null, $turnover = null, $odds = null, $win_loss = null, $game_info = null)
    {
        $this->win_amount = $win_amount;
        $this->closed_on = $closed_on;
        is_null($rebate) ?: $this->rebate = $rebate;
        is_null($comm) ?: $this->comm = $comm;
        is_null($valid_bet) ?: $this->valid_bet = $valid_bet;
        is_null($turnover) ?: $this->turnover = $turnover;
        is_null($odds) ?: $this->odds = $odds;
        is_null($win_loss) ?: $this->win_loss = $win_loss;
        is_null($game_info) ?: $this->game_info = $game_info;
        if ($this->status == BetConstants::STATUS_SETTLED) {
            $this->status = BetConstants::STATUS_RESETTLED;
        } else {
            $this->status = BetConstants::STATUS_SETTLED;
        }
        $this->save();
    }

    public function unsettle($win_amount = null, $win_loss = null)
    {
        $this->win_amount = $win_amount;
        $this->closed_on = null;
        $this->win_loss = $win_loss;
        $this->status = BetConstants::STATUS_UNSETTLED;
        $this->save();
    }

    public function resettle($win_amount, $closed_on, $bet_on, $rebate, $comm, $valid_bet, $turnover, $odds, $win_loss = null)
    {
        $this->bet_on = $bet_on;
        $this->odds = $odds;
        $this->comm = $comm;
        $this->win_amount = $win_amount;
        $this->closed_on = $closed_on;
        $this->rebate = $rebate;
        $this->valid_bet = $valid_bet;
        $this->turnover = $turnover;
        $this->odds = $odds;
        $this->win_loss = $win_loss;
        $this->status = BetConstants::STATUS_RESETTLED;
        $this->save();
    }

    public function cancel($closed_on)
    {
        $this->closed_on = $closed_on;
        $this->status = BetConstants::STATUS_CANCELED;
        $this->save();
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    // scopes 

    public function scopeStatus($query, $status)
    {
        $query->where('status', $status);
    }

    public static function scopeCmdActiveIdLogic($query, $action_id)
    {
        match ($action_id) {
            CMDProvider::ACTION_ID_RESETTLE_TICKET => $query->statusIn([BetConstants::STATUS_UNSETTLED, BetConstants::STATUS_CANCELED]),
            CMDProvider::ACTION_ID_BT_BUY_BACK => $query->statusNot(BetConstants::STATUS_CANCELED),
            CMDProvider::ACTION_ID_SETTLE_HT,
            CMDProvider::ACTION_ID_SETTLE_FT,
            CMDProvider::ACTION_ID_DANGER_REFUND,
            CMDProvider::ACTION_ID_CANCEL_HT,
            CMDProvider::ACTION_ID_CANCEL_FT,
            CMDProvider::ACTION_ID_SETTLE_PARLAY => $query->status(BetConstants::STATUS_UNSETTLED),
            CMDProvider::ACTION_ID_UNSETTLE_HT,
            CMDProvider::ACTION_ID_UNSETTLE_FT,
            CMDProvider::ACTION_ID_UNSETTLE_PARLAY => $query->statusIn([BetConstants::STATUS_SETTLED, BetConstants::STATUS_RESETTLED]),
            CMDProvider::ACTION_ID_UNCANCEL_HT,
            CMDProvider::ACTION_ID_UNCANCEL_FT => $query->status(BetConstants::STATUS_CANCELED),
                //ACTION_ID_SYSTEM_ADJUSTMENT no need for filtering
            default => $query
        };
    }

    public function scopeStatusNot($query, $status)
    {
        $query->where('status', '!=', $status);
    }

    public function scopeStatusIn($query, array $statuses)
    {
        $query->whereIn('status', $statuses);
    }

    public function scopeStatusNotIn($query, array $statuses)
    {
        $query->whereNotIn('status', $statuses);
    }

    public function scopeReference($query, $reference)
    {
        $query->where('bet_reference', $reference);
    }

    public function scopeReferenceIn($query, $bet_references)
    {
        $query->whereIn('bet_reference', $bet_references);
    }

    public function scopeBetRoundReference($query, $bet_round_reference)
    {
        $query->where('bet_round_reference', $bet_round_reference);
    }

    public function scopeBetRoundReferenceIn($query, $bet_round_references)
    {
        $query->whereIn('bet_round_reference', $bet_round_references);
    }

    public function scopePlayerId($query, $player_id)
    {
        $query->whereHas('BetRound', function ($query) use ($player_id) {

            $query->where('player_id', $player_id);
        });
    }
}
