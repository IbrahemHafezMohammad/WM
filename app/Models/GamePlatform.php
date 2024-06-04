<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class GamePlatform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon_image',
        'platform_code'
    ];

    protected function iconImage(): Attribute
    {
        return new Attribute(
            get: fn($value) => $value ? Storage::url($value) : $value
        );
    }

    public function gameItems(): HasMany
    {
        return $this->hasMany(GameItem::class);
    }

    public function apiHits(): HasMany
    {
        return $this->hasMany(ApiHit::class, 'game_platform_id');
    }

    public function betRounds(): HasMany
    {
        return $this->hasMany(BetRound::class);
    }

    //custom function

    public static function getGamePlatforms($searchParams)
    {
        $query = GamePlatform::query();

        if (array_key_exists('search', $searchParams) && !is_null($searchParams['search'])) {
            $query->name($searchParams['search']);
        }

        if (array_key_exists('game_platform_id', $searchParams) && !is_null($searchParams['game_platform_id'])) {
            $query->platformId($searchParams['game_platform_id']);
        }

        return $query;
    }

    public function scopeName($query, $name)
    {
        $query->where('name', 'like', '%' . $name . '%');
    }

    public function scopePlatformId($query, $platformId)
    {
        $query->where('id', $platformId);
    }
}