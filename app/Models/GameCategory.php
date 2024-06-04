<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Constants\GameItemConstants;
use function PHPUnit\Framework\isNull;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Constants\GameCategoryConstants;
use App\Constants\GameItemGameCategoryConstants;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GameCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'sort_order',
        'icon_image',
        'icon_active',
        'icon_image_desktop',
        'icon_active_desktop',
        'is_lobby',
        'parent_category_id',
        'updated_by',
        'icon_trend',
        'properties',
        'bg_image'
    ];

    protected $appends = ['properties_array'];

    public function getPropertiesArrayAttribute()
    {
        $properties_array = [];
        $properties = GameCategoryConstants::getProperties();
        foreach ($properties as $property_name => $property_value) {
            $arr = [];
            $this->properties & $property_value ? $arr[$property_name] = true : $arr[$property_name] = false;
            $arr['id'] = $property_value;
            $properties_array[] = $arr;
        }
        return $properties_array;
    }

    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(GameCategory::class, 'parent_category_id');
    }

    public function childCategories(): HasMany
    {
        return $this->hasMany(GameCategory::class, 'parent_category_id');
    }

    protected function iconImage(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? Storage::url($value) : $value
        );
    }

    protected function iconActive(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? Storage::url($value) : $value
        );
    }

    protected function iconTrend(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? Storage::url($value) : $value
        );
    }

    protected function iconImageDesktop(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? Storage::url($value) : $value
        );
    }

    protected function iconActiveDesktop(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? Storage::url($value) : $value
        );
    }

    protected function bgImage(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? Storage::url($value) : $value
        );
    }

    public function gameItems(): BelongsToMany
    {
        return $this->belongsToMany(GameItem::class, GameItemGameCategoryConstants::TABLE_NAME)->withPivot('game_item_sort_order')->orderBy('game_item_sort_order');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    public function deleteIconImage()
    {
        if ($this->icon_image) {
            return Storage::delete(substr($this->icon_image, strpos($this->icon_image, GlobalConstants::GAME_CATEGORY_IMAGES_PATH)));
        }
        return true;
    }

    public function deleteIconActive()
    {
        if ($this->icon_active) {
            return Storage::delete(substr($this->icon_active, strpos($this->icon_active, GlobalConstants::GAME_CATEGORY_IMAGES_PATH)));
        }
        return true;
    }

    // public static function getAllGameCategoriesWithGames($currency)
    // {
    //     return self::with([
    //         'updatedBy:id,user_name',
    //         'parentCategory',
    //         'childCategories' => function ($query){
    //             $query->where('status' , GameItemConstants::STATUS_ACTIVE);
    //         },
    //         'childCategories.gameItems',
    //         'gameItems' => function ($query) use ($currency) {
    //             $query->currency($currency)->where('status' , GameItemConstants::STATUS_ACTIVE)->with('gamePlatform')->orderBy('pivot_game_item_sort_order');
    //         }
    //     ]);

    // }

    public static function getGameCategoriesWithGames(int $currency, bool $is_desktop, bool $is_mobile, $game_name, $platform_ids)
    {
        return self::with([
            // 'updatedBy:id,user_name',
            'childCategories' => function ($query) use ($currency, $is_desktop, $is_mobile, $game_name, $platform_ids) {

                $query->where('status', true)->where(function ($query) use ($currency) {
                    $query->whereHas('gameItems', function ($query) use ($currency) {

                        $query->currency($currency)->where('status', GameItemConstants::STATUS_ACTIVE);
                    })->orWhereHas('childCategories', function ($query) use ($currency) {

                        $query->where('status', true)->whereHas('gameItems', function ($query) use ($currency) {

                            $query->currency($currency)->where('status', GameItemConstants::STATUS_ACTIVE);
                        });
                    });
                })->with(['gameItems' => function ($query) use ($currency, $platform_ids, $game_name) {

                    is_null($game_name) ?: $query->where('name', 'like', '%' . $game_name . '%');

                    is_null($platform_ids) ?: $query->whereIn('game_platform_id', $platform_ids);

                    $query->currency($currency)->where('status', GameItemConstants::STATUS_ACTIVE)->with('gamePlatform')->orderBy('pivot_game_item_sort_order');
                }])->with(['childCategories' => function ($query) use ($currency, $is_desktop, $is_mobile, $game_name, $platform_ids) {

                    $query->where('status', true)->whereHas('gameItems', function ($query) use ($currency) {

                        $query->currency($currency)->where('status', GameItemConstants::STATUS_ACTIVE);
                    })->with(['gameItems' => function ($query) use ($currency, $platform_ids, $game_name) {

                        is_null($game_name) ?: $query->where('name', 'like', '%' . $game_name . '%');

                        is_null($platform_ids) ?: $query->whereIn('game_platform_id', $platform_ids);

                        $query->currency($currency)->where('status', GameItemConstants::STATUS_ACTIVE)->with('gamePlatform')->orderBy('pivot_game_item_sort_order');
                    }]);

                    if ($is_desktop) {

                        $query->where(function ($query) {

                            $query->property(GameCategoryConstants::PROPERTY_DESKTOP_SHOW)->orWhere(function ($query) {

                                $query->notProperty(GameCategoryConstants::PROPERTY_DESKTOP_SHOW)->notProperty(GameCategoryConstants::PROPERTY_MOBILE_SHOW);
                            });
                        });
                    }

                    if ($is_mobile) {

                        $query->where(function ($query) {

                            $query->property(GameCategoryConstants::PROPERTY_MOBILE_SHOW)->orWhere(function ($query) {

                                $query->notProperty(GameCategoryConstants::PROPERTY_DESKTOP_SHOW)->notProperty(GameCategoryConstants::PROPERTY_MOBILE_SHOW);
                            });
                        });
                    }
                }]);

                if ($is_desktop) {

                    $query->where(function ($query) {

                        $query->property(GameCategoryConstants::PROPERTY_DESKTOP_SHOW)->orWhere(function ($query) {

                            $query->notProperty(GameCategoryConstants::PROPERTY_DESKTOP_SHOW)->notProperty(GameCategoryConstants::PROPERTY_MOBILE_SHOW);
                        });
                    });
                }

                if ($is_mobile) {

                    $query->where(function ($query) {

                        $query->property(GameCategoryConstants::PROPERTY_MOBILE_SHOW)->orWhere(function ($query) {

                            $query->notProperty(GameCategoryConstants::PROPERTY_DESKTOP_SHOW)->notProperty(GameCategoryConstants::PROPERTY_MOBILE_SHOW);
                        });
                    });
                }
            },
            // 'gameItems' => function ($query) use ($currency, $platform_ids, $game_name) {

            //     is_null($game_name) ?: $query->where('name', 'like', '%' . $game_name . '%');

            //     is_null($platform_ids) ?: $query->whereIn('game_platform_id', $platform_ids);

            //     $query->currency($currency)->where('status', GameItemConstants::STATUS_ACTIVE)->with('gamePlatform')->orderBy('pivot_game_item_sort_order')->paginate(15);
            // }
        ]);
    }

    public static function getAllCategoryGames()
    {
        return self::with([
            'gameItems' => function ($query) {
                $query->with('gamePlatform')->where('status', true)->orderBy('pivot_game_item_sort_order');
            }
        ]);
    }

    public function scopeName($query, $name)
    {
        $query->where('name', 'like', '%' . $name . '%');
    }

    public function scopeProperty($query, $property)
    {
        $query->whereRaw('properties & ' . $property . ' = ' . $property);
    }

    public function scopeNotProperty($query, $property)
    {
        $query->whereRaw('properties & ' . $property . ' = 0');
    }

    public function scopeOrProperty($query, $property)
    {
        $query->orWhereRaw('properties & ' . $property . ' = ' . $property);
    }

    public function scopeCategoryId($query, $game_category_id)
    {
        $query->where('id', $game_category_id);
    }

    public static function changeGameCategoriesSortOrder($orders)
    {
        $ids = array_column($orders, 'id');
        $cases = '';

        foreach ($orders as $order) {
            $cases .= "WHEN {$order['id']} THEN {$order['sort_order']} ";
        }

        $cases = "CASE id {$cases} ELSE sort_order END";

        return self::whereIn('id', $ids)->update(['sort_order' => DB::raw($cases)]);
    }

    public function changeGameItemsSortOrder($orders)
    {
        foreach ($orders as $order) {
            $game_item_id = $order['id'];
            $sort_order = $order['sort_order'];

            // Update the pivot data
            $this->gameItems()->updateExistingPivot($game_item_id, ['game_item_sort_order' => $sort_order]);
        }
    }

    public static function getWithGameItems()
    {
        return self::where('status', GameCategoryConstants::IS_ACTIVE)
            ->whereHas('gameItems')
            ->with([
                'gameItems' => function ($query) {
                    $query->where('status', '!=', GameItemConstants::STATUS_INACTIVE)
                        ->orderBy('pivot_game_item_sort_order');
                }
            ]);
    }
}
