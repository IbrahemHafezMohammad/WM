<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use Illuminate\Database\Eloquent\Model;
use App\Constants\UGProviderConfigConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UGProviderConfig extends Model
{
    use HasFactory;

    protected $table = UGProviderConfigConstants::TABLE_NAME;

    protected $fillable = [
        'player_id',
        'odds_expression',
        'template',
        'theme',
        'game_mode',
        'favorite_sport',
        'default_market',
    ];


    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
