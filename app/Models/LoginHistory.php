<?php

namespace App\Models;

use App\Constants\UserConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class LoginHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip',
        'note',
        'device_type',
        'browser_type'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    //custom function

    public static function getPlayerLoginHistory($searchParams)
    {
        $query = LoginHistory::latest()->whereHas('user.player')->with('user:id,user_name');

        if (array_key_exists('search', $searchParams)) {
            $query->playerUserName($searchParams['search']);
        }

        if (array_key_exists('ip', $searchParams)) {
            $query->ip($searchParams['ip']);
        }

        if (array_key_exists('start_time', $searchParams)) {
            $query->startTime($searchParams['start_time']);
        }

        if (array_key_exists('end_time', $searchParams)) {
            $query->endTime($searchParams['end_time']);
        }

        return $query;
    }

    public static function getAdminLoginHistory($searchParams)
    {
        $query = LoginHistory::latest()->whereHas('user.admin')->with('user:id,user_name');

        if (array_key_exists('search', $searchParams)) {
            $query->adminUserName($searchParams['search']);
        }

        if (array_key_exists('ip', $searchParams)) {
            $query->ip($searchParams['ip']);
        }

        if (array_key_exists('start_time', $searchParams)) {
            $query->startTime($searchParams['start_time']);
        }

        if (array_key_exists('end_time', $searchParams)) {
            $query->endTime($searchParams['end_time']);
        }

        return $query;
    }

    public function scopePlayerUserName($query, $user_name)
    {
        $query->whereHas('user.player', function (Builder $query) use ($user_name) {

            $query->where(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $user_name . '%');
        });
    }

    public function scopeAdminUserName($query, $user_name)
    {
        $query->whereHas('user.admin', function (Builder $query) use ($user_name) {

            $query->where(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $user_name . '%');
        });
    }

    public function scopeIp($query, $ip)
    {
        $query->where('ip', 'like', '%' . $ip . '%');
    }

    public function scopeStartTime($query, $start_time)
    {
        $query->where('created_at', '>=', $start_time);
    }

    public function scopeEndTime($query, $end_time)
    {
        $query->where('created_at', '<=', $end_time);
    }
}
