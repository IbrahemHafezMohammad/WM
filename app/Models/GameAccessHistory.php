<?php

namespace App\Models;

use App\Constants\GameAccessHistoryConstants;
use App\Constants\GameCategoryConstants;
use App\Constants\GameItemConstants;
use App\Constants\GamePlatformConstants;
use App\Constants\UserConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Database\Eloquent\Builder as EagerBuilder;
use Illuminate\Database\Eloquent\Builder;

class GameAccessHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'remark',
        'status',
        'game_item_id'
    ];

    public function gameItem(): BelongsTo
    {
        return $this->belongsTo(GameItem::class, 'game_item_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public static function gameLogin(Player $player, GameItem $game_item)
    {
        return self::make([
            'player_id' => $player->id,
            'game_item_id' => $game_item->id,
        ]);
    }

    public function gameActionFailed(string $remark = null)
    {
        $this->status = GameAccessHistoryConstants::STATUS_FAILURE;
        is_null($remark) ?: $this->remark = $remark;
        $this->save();
    }

    public function gameActionPending(string $remark = null)
    {
        $this->status = GameAccessHistoryConstants::STATUS_PENDING;
        is_null($remark) ?: $this->remark = $remark;
        $this->save();
    }

    public function gameActionSuccess(string $remark = null)
    {
        $this->status = GameAccessHistoryConstants::STATUS_SUCCESS;
        is_null($remark) ?: $this->remark = $remark;
        $this->save();
    }

    public static function getGameAccessHistoriesWithRelations($searchParams)
    {
        $query = self::with([
            'player' => function ($query) {
                $query->with('user:id,user_name')->select(['id', 'user_id']);
            },
            'gameItem' => function ($query) {
                $query->with('gamePlatform:id,name', 'gameCategories:id,name')->select(['id', 'name', 'game_platform_id']);
            }
        ]);

        if (array_key_exists('from_date', $searchParams)) {

            $query->fromDate($searchParams['from_date']);
        }

        if (array_key_exists('to_date', $searchParams)) {

            $query->toDate($searchParams['to_date']);
        }

        if (array_key_exists('platform_name', $searchParams)) {

            $query->platformName($searchParams['platform_name']);
        }

        if (array_key_exists('game_category_name', $searchParams)) {

            $query->gameCategoryName($searchParams['game_category_name']);
        }

        if (array_key_exists('game_name', $searchParams)) {

            $query->gameName($searchParams['game_name']);
        }

        if (array_key_exists('player_name', $searchParams)) {

            $query->playerName($searchParams['player_name']);
        }

        if (array_key_exists('game_platform_id', $searchParams)) {

            $query->gamePlatformId($searchParams['game_platform_id']);
        }

        if (array_key_exists('game_category_id', $searchParams)) {

            $query->gameCategoryId($searchParams['game_category_id']);
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

    public function scopePlatformName($query, $platform_name)
    {
        $query->whereHas('gameItem.gamePlatform', function (Builder $query) use ($platform_name) {

            $query->where(GamePlatformConstants::TABLE_NAME . '.name', 'like', '%' . $platform_name . '%');
        });
    }

    public function scopeGamePlatformId($query, $platform_id)
    {
        $query->whereHas('gameItem.gamePlatform', function (Builder $query) use ($platform_id) {

            $query->where(GamePlatformConstants::TABLE_NAME . '.id', $platform_id);
        });
    }

    public function scopeGameCategoryId($query, $game_category_id)
    {
        $query->whereHas('gameItem.gameCategories', function (Builder $query) use ($game_category_id) {

            $query->where(GameCategoryConstants::TABLE_NAME . '.id', $game_category_id);
        });
    }

    public function scopeGameCategoryName($query, $game_category_name)
    {
        $query->whereHas('gameItem.gameCategories', function (Builder $query) use ($game_category_name) {

            $query->where(GameCategoryConstants::TABLE_NAME . '.name', 'like', '%' . $game_category_name . '%');
        });
    }

    public function scopeGameName($query, $game_name)
    {
        $query->whereHas('gameItem', function (Builder $query) use ($game_name) {

            $query->where(GameItemConstants::TABLE_NAME . '.name', 'like', '%' . $game_name . '%');
        });
    }

    public function scopePlayerName($query, $player_name)
    {
        $query->whereHas('player.user', function (Builder $query) use ($player_name) {

            $query->where(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $player_name . '%');
        });
    }

}