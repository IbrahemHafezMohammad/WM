<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use App\Constants\LoginHistoryConstants;
use App\Constants\TransactionConstants;
use App\Constants\UserConstants;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Database\Eloquent\Builder as EagerBuilder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;


class Player extends Model
{
    // use HasFactory;
    use HasApiTokens, HasFactory, Notifiable;
    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'user_id',
        'active',
        'type',
        'allow_withdraw',
        'allow_betting',
        'allow_deposit',
        'language',
        'points',
        'player_level'
    ];

    protected $with = ['wallet', 'user'];

    // appended attributes
    protected $appends = ['language_name'];

    public function getLanguageNameAttribute()
    {
        return GlobalConstants::getLanguage($this->language);
    }

    //relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function successFullDeposit(): HasMany
    {
        return $this->hasMany(Transaction::class)->where('status', TransactionConstants::STATUS_APPROVED)->where('isWithdraw', false);
    }
    public function successFullWithdraw(): HasMany
    {
        return $this->hasMany(Transaction::class)->where('status', TransactionConstants::STATUS_APPROVED)->where('isWithdraw', true);
    }
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'player_id');
    }

    public function gameTransactionHistories(): HasMany
    {
        return $this->hasMany(GameTransactionHistory::class);
    }

    public function agentChangeHistories(): HasMany
    {
        return $this->hasMany(AgentChangeHistory::class);
    }

    public function playerBalanceHistories(): HasMany
    {
        return $this->hasMany(PlayerBalanceHistory::class);
    }

    public function gameAccessHistories(): HasMany
    {
        return $this->hasMany(GameAccessHistory::class);
    }

    public function playerNotifications(): HasMany
    {
        return $this->hasMany(PlayerNotification::class);
    }

    public function betRounds(): HasMany
    {
        return $this->hasMany(BetRound::class);
    }

    public function kmProviderConfig(): HasOne
    {
        return $this->hasOne(KMProviderConfig::class);
    }

    public function ugProviderConfig(): HasOne
    {
        return $this->hasOne(UGProviderConfig::class);
    }

    public function evoProviderConfig(): HasOne
    {
        return $this->hasOne(EVOProviderConfig::class);
    }

    public function awcProviderConfig(): HasOne
    {
        return $this->hasOne(AWCProviderConfig::class);
    }

    public function playerRatings(): HasMany
    {
        return $this->hasMany(PlayerRating::class);
    }

    //custom functions

    public function profile()
    {
        return [
            'id' => $this->id,
            'phone' => $this->user->phone,
            'user_name' => $this->user->user_name,
            'name' => $this->user->name,
            'balance' => $this->wallet->balance,
            'currency' => $this->wallet->currency_name,
            'profile_pic' => $this->user->profile_pic,
            'language' => $this->language,
            'language_name' => $this->language_name,
            'created_at' => $this->user->created_at,
        ];
    }

    public static function listPlayers($searchParams, $can_view_phone)
    {

        $query = Player::with([
            'user' => function ($query) use ($can_view_phone) {
                
                $columns = ['id', 'name', 'user_name'];
                $can_view_phone ? $columns[] = 'phone' : null;
                $query->with(['signupHistory', 'latestLoginHistory'])->select($columns);
            },
            'agent' => function ($query) {
                $query->with(['user:id,user_name,name'])->select(['id', 'user_id']);
            },
            'wallet'
        ]);

        if (array_key_exists('status', $searchParams)) {

            $query->status($searchParams['status']);
        }

        if (array_key_exists('start_date', $searchParams)) {

            $query->startDate($searchParams['start_date']);
        }

        if (array_key_exists('end_date', $searchParams)) {

            $query->endDate($searchParams['end_date']);
        }

        if (array_key_exists('search', $searchParams)) {

            $query->searchUsernameOrSignupOrLogin($searchParams['search']);
        }

        return $query;
    }

    public function scopeStatus($query, $status)
    {
        $query->where('active', $status);
    }

    public function scopeStartDate($query, $date)
    {
        $query->whereHas('user', function (Builder $query) use ($date) {

            $query->where(UserConstants::TABLE_NAME . '.created_at', '>=', $date);
        });
    }

    public function scopeEndDate($query, $date)
    {
        $query->whereHas('user', function (Builder $query) use ($date) {

            $query->where(UserConstants::TABLE_NAME . '.created_at', '<=', $date);
        });
    }

    public function scopeSearchUsernameOrSignupOrLogin($query, $search)
    {
        $query->whereHas('user', function (Builder $query) use ($search) {

            $query->where(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $search . '%');
        })->orWhereHas('user.signupHistory', function (Builder $query) use ($search) {

            $query->where(LoginHistoryConstants::TABLE_NAME . '.ip', 'like', '%' . $search . '%');
        })->orWhereHas('user.latestLoginHistory', function (Builder $query) use ($search) {

            $query->where(LoginHistoryConstants::TABLE_NAME . '.ip', 'like', '%' . $search . '%');
        });
    }

    public static function view()
    {
        return self::with([
            'agent' => function ($query) {
                $query->with(['user:id,name,user_name'])->select(['id', 'user_id']);
            },
            'user' => function ($query) {
                $query->with(['signupHistory', 'latestLoginHistory', 'userPaymentMethods.bankCode', 'userPaymentMethods.paymentCategory'])->get();
            },
        ]);
    }

    public static function latestLogin()
    {
        return self::with([
            'user:id',
            'user.loginHistory' => function ($query) {

                $query->orderBy('id', 'desc')->limit(7);
            }
        ]);
    }
}
