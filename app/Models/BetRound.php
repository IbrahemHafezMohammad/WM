<?php

namespace App\Models;

use Carbon\Carbon;
use App\Constants\BetConstants;
use App\Constants\UserConstants;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\DB;
use App\Constants\BetRoundConstants;
use App\Models\GameTransactionHistory;
use Illuminate\Database\Eloquent\Model;
use App\Constants\GameTransactionHistoryConstants;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BetRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'game_platform_id',
        'provider',
        'ip_address',
        'device',
        'round_reference',
        'started_on',
        'ended_on',
        'status',
        'currency',
        'win_loss',
        'total_valid_bets',
        'total_turnovers',
        'total_win_amount',
        'expected_winloss_on',
        'lifecycle'
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

    protected $appends = [
        'currency_name',
        'status_name',
        'base_win_loss',
        'base_total_turnovers',
        'base_total_valid_bets',
        'base_total_win_amount',
    ];

    public function getCurrencyNameAttribute()
    {
        return GlobalConstants::getCurrency($this->currency);
    }

    public function getStatusNameAttribute()
    {
        return BetRoundConstants::getStatus($this->status);
    }

    public function getBaseTotalWinAmountAttribute()
    {
        return $this->total_win_amount ? round($this->total_win_amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getBaseTotalValidBetsAttribute()
    {
        return $this->total_valid_bets ? round($this->total_valid_bets * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getBaseWinLossAttribute()
    {
        return $this->win_loss ? round($this->win_loss * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getBaseTotalTurnoversAttribute()
    {
        return $this->total_turnovers ? round($this->total_turnovers * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    // relations

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function gamePlatform(): BelongsTo
    {
        return $this->belongsTo(GamePlatform::class);
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class, 'bet_round_id');
    }

    public function settledAndResettledBets(): HasMany
    {
        return $this->hasMany(Bet::class, 'bet_round_id')->whereIn('status', [BetConstants::STATUS_SETTLED, BetConstants::STATUS_RESETTLED]);
    }

    public function unsettledBets(): HasMany
    {
        return $this->hasMany(Bet::class, 'bet_round_id')->where('status', BetConstants::STATUS_UNSETTLED);
    }

    public function settledBets(): HasMany
    {
        return $this->hasMany(Bet::class, 'bet_round_id')->where('status', BetConstants::STATUS_SETTLED);
    }

    public function resettledBets(): HasMany
    {
        return $this->hasMany(Bet::class, 'bet_round_id')->where('status', BetConstants::STATUS_RESETTLED);
    }

    public function gameTransactionHistories(): HasMany
    {
        return $this->hasMany(GameTransactionHistory::class, 'bet_round_id');
    }

    public function latestGameTransactionHistory(): HasOne
    {
        return $this->hasOne(GameTransactionHistory::class, 'bet_round_id')->latestOfMany();
    }

    public function latestSuccessfulGameTransactionHistory(): HasOne
    {
        return $this->hasOne(GameTransactionHistory::class, 'bet_round_id')->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->latestOfMany();
    }

    public function latestFailedGameTransactionHistory(): HasOne
    {
        return $this->hasOne(GameTransactionHistory::class, 'bet_round_id')->status(GameTransactionHistoryConstants::STATUS_FAILURE)->latestOfMany();
    }
    // custom function

    public static function begin(
        $player_id,
        $game_platform_id,
        $round_reference,
        $started_on,
        $currency,
        $device = null,
        $provider = null,
        $ip_address = null,
        $expected_winloss_on = null
    ) {
        return self::create([
            'player_id' => $player_id,
            'game_platform_id' => $game_platform_id,
            'provider' => $provider,
            'ip_address' => $ip_address,
            'device' => $device,
            'round_reference' => $round_reference,
            'started_on' => $started_on,
            'currency' => $currency,
            'expected_winloss_on' => $expected_winloss_on,
            'status' => BetRoundConstants::STATUS_OPEN,
        ]);
    }

    public function close($ended_on, $win_loss, $total_turnovers = null, $total_valid_bets = null, $total_win_amount = null)
    {
        $this->ended_on = $ended_on;
        $this->win_loss = $win_loss;
        is_null($total_turnovers) ?: $this->total_turnovers = $total_turnovers;
        is_null($total_valid_bets) ?: $this->total_valid_bets = $total_valid_bets;
        is_null($total_win_amount) ?: $this->total_win_amount = $total_win_amount;
        if ($this->status == BetRoundConstants::STATUS_CLOSED || $this->status == BetRoundConstants::STATUS_RECLOSED) {
            $this->status = BetRoundConstants::STATUS_RECLOSED;
        } else {
            $this->status = BetRoundConstants::STATUS_CLOSED;
        }
        $this->save();
    }

    public function reopen($win_loss, $total_turnovers, $total_valid_bets, $total_win_amount)
    {
        $this->win_loss = $win_loss;
        $this->total_turnovers = $total_turnovers;
        $this->total_valid_bets = $total_valid_bets;
        $this->total_win_amount = $total_win_amount;
        $this->ended_on = null;
        $this->status = BetRoundConstants::STATUS_REOPEN;
        $this->save();
    }

    public function reclose(
        $ended_on,
        $win_loss,
        $device = null,
        $ip_address = null,
        $provider = null,
        $total_turnovers = null,
        $total_valid_bets = null,
        $total_win_amount = null
    ) {
        $this->ended_on = $ended_on;
        $this->win_loss = $win_loss;
        is_null($provider) ?: $this->provider = $provider;
        is_null($ip_address) ?: $this->ip_address = $ip_address;
        is_null($device) ?: $this->device = $device;
        is_null($total_turnovers) ?: $this->total_turnovers = $total_turnovers;
        is_null($total_valid_bets) ?: $this->total_valid_bets = $total_valid_bets;
        is_null($total_win_amount) ?: $this->total_win_amount = $total_win_amount;
        $this->status = BetRoundConstants::STATUS_RECLOSED;
        $this->save();
    }

    public function adjust(
        $win_loss,
        $ended_on = null,
        $total_turnovers = null,
        $total_valid_bets = null,
        $total_win_amount = null,
        $round_reference = null,
        $started_on = null,
        $device = null,
        $provider = null,
        $ip_address = null,
        $expected_winloss_on = null,
    ) {
        $this->win_loss = $win_loss;
        $this->ended_on = $ended_on;
        is_null($round_reference) ?: $this->round_reference = $round_reference;
        is_null($started_on) ?: $this->started_on = $started_on;
        is_null($provider) ?: $this->provider = $provider;
        is_null($ip_address) ?: $this->ip_address = $ip_address;
        is_null($device) ?: $this->device = $device;
        is_null($expected_winloss_on) ?: $this->expected_winloss_on = $expected_winloss_on;
        is_null($total_turnovers) ?: $this->total_turnovers = $total_turnovers;
        is_null($total_valid_bets) ?: $this->total_valid_bets = $total_valid_bets;
        is_null($total_win_amount) ?: $this->total_win_amount = $total_win_amount;
        $this->save();
    }


    // BO function

    public static function getMonthWinloss()
    {
        $closed_rounds = self::statusIn([BetRoundConstants::STATUS_CLOSED, BetRoundConstants::STATUS_RECLOSED])
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->get();

        return $closed_rounds->sum(function ($round) {
            return $round->base_win_loss;
        });
    }

    public function getDetails()
    {
        $this->loadSum([
            'bets' => function ($query) {
                $query->select(DB::raw('SUM(bet_amount)'));
            }
        ], 'total_bet_amount');
        // ->loadSum([
        //     'bets' => function ($query) {
        //         $query->select(DB::raw('SUM(valid_bet)'));
        //     }
        // ], 'total_valid_bet')
        // ->loadSum([
        //     'bets' => function ($query) {
        //         $query->select(DB::raw('SUM(turnover)'));
        //     }
        // ], 'total_turnover')
        // ->loadSum([
        //     'bets' => function ($query) {
        //         $query->select(DB::raw('SUM(win_amount)'));
        //     }
        // ], 'total_win_amount');

        $this->load([
            'player',
            'gamePlatform',
            'bets.gameItem',
            'bets.gameTransactionHistories',

        ]);

        return $this;
    }

    public static function getBetRoundsWithRelations(array $searchParams)
    {
       
        $query = BetRound::query();
        
        if (array_key_exists('date_type', $searchParams) && !is_null($searchParams['date_type']) && $searchParams['date_type'] == 'start') {

            if (array_key_exists('date_from', $searchParams) && !is_null($searchParams['date_from'])) {
                $query->startedFrom($searchParams['date_from']);
            }

            if (array_key_exists('date_to', $searchParams) && !is_null($searchParams['date_to'])) {
                $query->startedTo($searchParams['date_to']);
            }
        } elseif (array_key_exists('date_type', $searchParams) && !is_null($searchParams['date_type']) && $searchParams['date_type'] == 'end') {

            if (array_key_exists('date_from', $searchParams) && !is_null($searchParams['date_from'])) {
                $query->endedFrom($searchParams['date_from']);
            }

            if (array_key_exists('date_to', $searchParams) && !is_null($searchParams['date_to'])) {
                $query->endedTo($searchParams['date_to']);
            }
        }

        if (array_key_exists('user_name', $searchParams) && !is_null($searchParams['user_name'])) {
            $query->userName($searchParams['user_name']);
        }

        if (array_key_exists('phone', $searchParams) && !is_null($searchParams['phone'])) {
            $query->phone($searchParams['phone']);
        }

        if (array_key_exists('round_reference', $searchParams) && !is_null($searchParams['round_reference'])) {
            $query->roundReferenceLike($searchParams['round_reference']);
        }

        if (array_key_exists('status', $searchParams) && !is_null($searchParams['status'])) {
            $query->status($searchParams['status']);
        }

        if (array_key_exists('game_platform_id', $searchParams) && !is_null($searchParams['game_platform_id'])) {
            $query->gamePlatformId($searchParams['game_platform_id']);
        }

        if (array_key_exists('provider', $searchParams) && !is_null($searchParams['provider'])) {
            $query->provider($searchParams['provider']);
        }

        if (array_key_exists('ip_address', $searchParams) && !is_null($searchParams['ip_address'])) {
            $query->ipAddress($searchParams['ip_address']);
        }

        if (array_key_exists('device', $searchParams) && !is_null($searchParams['device'])) {
            $query->device($searchParams['device']);
        }

        if (array_key_exists('currency', $searchParams) && !is_null($searchParams['currency'])) {
            $query->currency($searchParams['currency']);
        }

        return $query;
    }
    // scopes

    public function scopeStartedFrom($query, $started_from)
    {
        $query->where('started_on', '>=', $started_from);
    }

    public function scopeStartedTo($query, $started_to)
    {
        $query->where('started_on', '<=', $started_to);
    }

    public function scopeEndedFrom($query, $ended_from)
    {
        $query->where('ended_on', '>=', $ended_from);
    }

    public function scopeEndedTo($query, $ended_to)
    {
        $query->where('ended_on', '<=', $ended_to);
    }

    public function scopeUserName($query, $user_name)
    {
        $query->whereHas('player.user', function ($query) use ($user_name) {
            $query->where(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $user_name . '%');
        });
    }

    public function scopePhone($query, $phone)
    {
        $query->whereHas('player.user', function ($query) use ($phone) {
            $query->where(UserConstants::TABLE_NAME . '.phone', 'like', '%' . $phone . '%');
        });
    }

    public function scopeRoundReference($query, $round_reference)
    {
        $query->where('round_reference', $round_reference);
    }

    public function scopeRoundReferenceIn($query, $round_references)
    {
        $query->whereIn('round_reference', $round_references);
    }

    public function scopeRoundReferenceLike($query, $round_reference)
    {
        $query->where('round_reference', 'like', '%' . $round_reference . '%');
    }

    public function scopeStatus($query, $status)
    {
        $query->where('status', $status);
    }

    public function scopeStatusIn($query, array $statuses)
    {
        $query->whereIn('status', $statuses);
    }

    public function scopeGamePlatformId($query, $game_platform_id)
    {
        $query->where('game_platform_id', $game_platform_id);
    }

    public function scopeProvider($query, $provider)
    {
        $query->where('provider', 'like', '%' . $provider . '%');
    }

    public function scopeIpAddress($query, $ip_address)
    {
        $query->where('ip_address', 'like', '%' . $ip_address . '%');
    }

    public function scopeDevice($query, $device)
    {
        $query->where('device', 'like', '%' . $device . '%');
    }

    public function scopeCurrency($query, $currency)
    {
        $query->where('currency', $currency);
    }

    public function scopePlayerId($query, $player_id)
    {
        $query->where('player_id', $player_id);
    }

    public static function getBetRounds(array $params)
    {
        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10;
        $query = BetRound::select([
            'game_items.name',
            'game_platforms.id as provider_id',
            'bet_rounds.id',
            'bet_rounds.started_on',
            'bet_rounds.ended_on',
            'bet_rounds.win_loss',
            'bet_rounds.total_win_amount',
            'bet_rounds.total_valid_bets',
            'bet_rounds.total_turnovers',
            'bet_rounds.round_reference',
        ])
        ->join('game_platforms', 'game_platforms.id', '=', 'bet_rounds.game_platform_id')
        ->join('bets', 'bets.bet_round_id', '=', 'bet_rounds.id')
        ->join('game_items', 'game_items.id', '=', 'bets.game_item_id')
        ->where('bet_rounds.player_id', $params['player_id']);
 
        $search  = ($params['provider']);
        // return $search;
        if (isset($search)) {
            $query->where('game_platforms.id', $search);
        }

        if (isset($params['from_date']) && isset($params['to_date'])) {
            $from_date = $params['from_date'];
            $to_date = $params['to_date'];
            $query->whereBetween('bets.bet_on', [$from_date, $to_date]);
        }

        $query->distinct();
        $query->orderBy('bet_on','desc');

        $betRounds =  $query->paginate($perPage);

        $sums = BetRound::selectRaw(
            'SUM(bet_rounds.win_loss) as total_win_loss, ' .
            'SUM(bet_rounds.total_win_amount) as total_win_amount, ' .
            'SUM(bet_rounds.total_valid_bets) as total_valid_bets, ' .
            'SUM(bet_rounds.total_turnovers) as total_turnovers'
        )
        ->join('bets', 'bets.bet_round_id', '=', 'bet_rounds.id')
        ->join('game_platforms', 'game_platforms.id', '=', 'bet_rounds.game_platform_id')
        ->where('bet_rounds.player_id', $params['player_id']);
        
        if ($search) {
            $sums->where('game_platforms.id', $search);
        }
        
        if (isset($params['from_date']) && isset($params['to_date'])) {
            $sums->whereBetween('bet_rounds.started_on', [$from_date, $to_date]);
        }
        
        $sums = $sums->first();
        
        $betRounds->getCollection()->transform(function ($item) {
            $item->status = BetConstants::getStatus($item->status); 
            return $item;
        });

        return response()->json([
            'betRounds' => $betRounds,
            'summary' => $sums,
        ]);
    }
}
