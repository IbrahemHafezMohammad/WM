<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use Illuminate\Database\Eloquent\Model;
use App\Constants\KMProviderConfigConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KMProviderConfig extends Model
{
    use HasFactory;

    protected $table = KMProviderConfigConstants::TABLE_NAME;

    protected $fillable = [
        'player_id',
        'vndk_user_id',
        'inr_user_id',
        'php_user_id',
        'bet_limit',
    ];

    protected $appends = [
        'bet_limit_name',
    ];

    public function getBetLimitNameAttribute()
    {
        return KMProviderConfigConstants::getBetLimit($this->bet_limit);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    //scopes
    public function scopeUserId($query, $userId, $system_currency)
    {
        if ($system_currency === GlobalConstants::CURRENCY_VNDK) {

            return $query->where('vndk_user_id', $userId);

        } elseif ($system_currency === GlobalConstants::CURRENCY_INR) {

            return $query->where('inr_user_id', $userId);

        } elseif ($system_currency === GlobalConstants::CURRENCY_PHP) {
            
            return $query->where('php_user_id', $userId);
        }
    }
}
