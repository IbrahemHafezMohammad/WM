<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Constants\AWCProviderConfigConstants;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AWCProviderConfig extends Model
{
    use HasFactory;

    protected $table = AWCProviderConfigConstants::TABLE_NAME;

    protected $fillable = [
        'player_id',
        'user_id',
        'auto_bet_mode',
        'vndk_bet_limit',
        'php_bet_limit',
        'inr_bet_limit',
    ];

    protected $casts = [
        'vndk_bet_limit' => 'array',
        'php_bet_limit' => 'array',
        'inr_bet_limit' => 'array'
    ];

    protected function autoBetMode(): Attribute
    {
        return new Attribute(
            get: fn($value) => $value ? '1' : '0'
        );
    }

    // relationship
    public function player()
    {
        return $this->belongsTo(Player::class);
    }


    // scope
    public function scopePlayerId($query, $player_id)
    {
        $query->where('player_id', $player_id);
    }

    public function scopeUserId($query, $user_id)
    {
        $query->where('user_id', $user_id);
    }

    public function scopeUserIdIn($query, $user_ids)
    {
        $query->whereIn('user_id', $user_ids);
    }
}
