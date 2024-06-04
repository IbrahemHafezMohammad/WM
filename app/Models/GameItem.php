<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\DB;
use App\Constants\GameItemConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Constants\GameCategoryConstants;
use App\Constants\GamePlatformConstants;
use Illuminate\Database\Eloquent\Builder;
use App\Constants\GameItemGameCategoryConstants;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_platform_id',
        'name',
        'icon_square',
        'icon_rectangle',
        'icon_square_desktop',
        'icon_rectangle_desktop',
        'status',
        'properties',
        'supported_currencies',
        'game_id'
    ];

    protected $appends = ['properties_array', 'supported_currencies_array'];

    public function getPropertiesArrayAttribute()
    {
        $properties_array = [];
        $properties = GameItemConstants::getProperties();
        foreach ($properties as $property_name => $property_value) {
            $arr = [];
            $this->properties & $property_value ? $arr[$property_name] = true : $arr[$property_name] = false;
            $arr['id'] = $property_value;
            $properties_array[] = $arr;
        }
        return $properties_array;
    }

    public function getSupportedCurrenciesArrayAttribute()
    {
        $supported_currencies_array = [];
        $currencies = GlobalConstants::getCurrencies();
        foreach ($currencies as $currency_value => $currency_name) {
            $arr = [];
            $this->supported_currencies & $currency_value ? $arr[$currency_name] = true : $arr[$currency_name] = false;
            $arr['id'] = $currency_value;
            $supported_currencies_array[] = $arr;
        }
        return $supported_currencies_array;
    }

    protected function iconSquare(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->isUrl($value) ? $value : ($value ? Storage::url($value) : null)
        );
    }

    protected function iconRectangle(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->isUrl($value) ? $value : ($value ? Storage::url($value) : null)
        );
    }

    protected function iconSquareDesktop(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->isUrl($value) ? $value : ($value ? Storage::url($value) : null)
        );
    }

    protected function iconRectangleDesktop(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->isUrl($value) ? $value : ($value ? Storage::url($value) : null)
        );
    }

    /**
     * Determine if the given value is a URL.
     *
     * @param  string|null  $value
     * @return bool
     */
    protected function isUrl(?string $value): bool
    {
        return $value && (Str::startsWith($value, 'http://') || Str::startsWith($value, 'https://'));
    }

    public function gameCategories(): BelongsToMany
    {
        return $this->belongsToMany(GameCategory::class, GameItemGameCategoryConstants::TABLE_NAME)->withPivot('game_item_sort_order');
    }

    public function gamePlatform(): BelongsTo
    {
        return $this->belongsTo(GamePlatform::class);
    }

    public function gameAccessHistories(): HasMany
    {
        return $this->hasMany(GameAccessHistory::class, 'game_item_id');
    }

    public function gameTransactionHistories(): HasMany
    {
        return $this->hasMany(GameTransactionHistory::class, 'game_item_id');
    }

    public function apiHits(): HasMany
    {
        return $this->hasMany(ApiHit::class, 'game_item_id');
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    //custom function

    public static function calcProperties(array $properties_array)
    {
        $properties = 0;

        foreach ($properties_array as $property) {

            $properties |= $property;
        }
        return $properties;
    }

    public function isProperty(int $property)
    {
        return $this->properties & $property;
    }

    public static function calcCurrencies(array $currencies_array)
    {
        $currencies = 0;

        foreach ($currencies_array as $currency) {

            $currencies |= $currency;
        }
        return $currencies;
    }

    public function isCurrency(int $currency)
    {
        return $this->supported_currencies & $currency;
    }

    public function deleteIconSquareImage()
    {
        if ($this->icon_square) {
            return Storage::delete(substr($this->icon_square, strpos($this->icon_square, GlobalConstants::GAME_ITEM_IMAGES_PATH)));
        }
        return true;
    }

    public function deleteIconRectangleImage()
    {
        if ($this->icon_rectangle) {
            return Storage::delete(substr($this->icon_rectangle, strpos($this->icon_rectangle, GlobalConstants::GAME_ITEM_IMAGES_PATH)));
        }
        return true;
    }

    public function deleteIconSquareDesktopImage()
    {
        if ($this->icon_square_desktop) {
            return Storage::delete(substr($this->icon_square_desktop, strpos($this->icon_square_desktop, GlobalConstants::GAME_ITEM_IMAGES_PATH)));
        }
        return true;
    }

    public function deleteIconRectangleDesktopImage()
    {
        if ($this->icon_rectangle_desktop) {
            return Storage::delete(substr($this->icon_rectangle_desktop, strpos($this->icon_rectangle_desktop, GlobalConstants::GAME_ITEM_IMAGES_PATH)));
        }
        return true;
    }

    public function deleteAllImages()
    {
        return $this->deleteIconSquareImage() && $this->deleteIconRectangleImage() && $this->deleteIconSquareDesktopImage() && $this->deleteIconRectangleDesktopImage();
    }

    public function updateGameCategories($game_category_ids)
    {
        foreach ($game_category_ids as $category_id) {
            $this->gameCategories()->syncWithoutDetaching([$category_id => ['game_item_sort_order' => 0]]);
        }

        $current_category_ids = $this->gameCategories()->pluck(GameCategoryConstants::TABLE_NAME . '.id')->toArray();
        $categories_to_detach = array_diff($current_category_ids, $game_category_ids);
        $this->gameCategories()->detach($categories_to_detach);
    }

    public static function getGameItems($searchParams)
    {
        $query = self::with([
            'gameCategories' => function ($query) {

                $query->select(GameCategoryConstants::TABLE_NAME . '.id', GameCategoryConstants::TABLE_NAME . '.name');
            },
            'gamePlatform' => function ($query) {

                $query->select(GamePlatformConstants::TABLE_NAME . '.id', GamePlatformConstants::TABLE_NAME . '.name');
            }
        ]);

        if (array_key_exists('name', $searchParams)) {

            $query->where('name', 'like', '%' . $searchParams['name'] . '%');
        }

        if (array_key_exists('game_code', $searchParams)) {

            $query->where('game_id', 'like', '%' . $searchParams['game_code'] . '%');
        }

        if (array_key_exists('game_category_id', $searchParams)) {

            $query->gameCategoryId($searchParams['game_category_id']);
        }

        if (array_key_exists('game_platform_id', $searchParams)) {

            $query->gamePlatformId($searchParams['game_platform_id']);
        }

        if (array_key_exists('property', $searchParams)) {

            $query->property($searchParams['property']);
        }

        if (array_key_exists('currency', $searchParams)) {

            $query->currency($searchParams['currency']);
        }

        return $query;
    }

    public function scopeProperty($query, $property)
    {
        $query->whereRaw('properties & ' . $property . ' = ' . $property);
    }

    public function scopeCurrency($query, $currency)
    {
        $query->whereRaw('supported_currencies & ' . $currency . ' = ' . $currency);
    }

    public function scopeGameCategoryId($query, $game_category_id)
    {
        $query->whereHas('gameCategories', function ($query) use ($game_category_id) {

            $query->where(GameCategoryConstants::TABLE_NAME . '.id', $game_category_id);
        });
    }

    public function scopeGamePlatformId($query, $id)
    {
        $query->where('game_platform_id', $id);
    }
}
