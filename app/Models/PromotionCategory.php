<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\DB;
use App\Constants\PromotionConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Constants\PromotionPromotionCategoryConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PromotionCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
        'icon_image',
        'icon_image_desktop',
        'updated_by',
    ];

    protected $appends = [
        'full_icon_image',
        'full_icon_image_desktop'
    ];

    protected $casts = [
        'name' => 'array'
    ];

    public function getFullIconImageAttribute()
    {
        return $this->icon_image ? Storage::url($this->icon_image) : null;
    }

    public function getFullIconImageDesktopAttribute()
    {
        return $this->icon_image_desktop ? Storage::url($this->icon_image_desktop) : null;
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, PromotionPromotionCategoryConstants::TABLE_NAME)->withPivot('promotion_sort_order');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    //custom function
    public function deleteIconImage()
    {
        if ($this->icon_image) {
            return Storage::delete(substr($this->icon_image, strpos($this->icon_image, GlobalConstants::PROMOTION_CATEGORY_IMAGES_PATH)));
        }
        return true;
    }

    public function deleteIconImageDesktop()
    {
        if ($this->icon_image_desktop) {
            return Storage::delete(substr($this->icon_image_desktop, strpos($this->icon_image_desktop, GlobalConstants::PROMOTION_CATEGORY_IMAGES_PATH)));
        }
        return true;
    }

    public static function getPromotionCategories($searchParams)
    {
        $query = self::with([
            'updatedBy:id,user_name',
            'promotions' => function ($query) {
                $query->orderBy('pivot_promotion_sort_order');
            }
        ]);

        if (array_key_exists('search', $searchParams)) {
            $query->name($searchParams['search']);
        }

        return $query;
    }

    public static function getUniqueLanguages(): array
    {
        $allNames = self::all('name');
        $languages = [];

        foreach ($allNames as $name) {
            if (is_array($name->name)) {
                $languages = array_merge($languages, array_keys($name->name));
            }
        }

        return array_unique($languages);
    }

    public function scopeName($query, $name)
    {
        $languages = self::getUniqueLanguages();

        foreach ($languages as $lang) {
          $query->orWhere("name->$lang", 'LIKE', "%$name%");
        }
    }

    public static function changePromotionCategoriesSortOrder($orders)
    {
        $ids = array_column($orders, 'id');
        $cases = '';

        foreach ($orders as $order) {
            $cases .= "WHEN {$order['id']} THEN {$order['sort_order']} ";
        }

        $cases = "CASE id {$cases} ELSE sort_order END";

        return self::whereIn('id', $ids)->update(['sort_order' => DB::raw($cases)]);
    }

    public function changePromotionsSortOrder($orders)
    {
        foreach ($orders as $order) {
            $promotion_id = $order['id'];
            $sort_order = $order['sort_order'];

            // Update the pivot data
            $this->promotions()->updateExistingPivot($promotion_id, ['promotion_sort_order' => $sort_order]);
        }
    }

    public static function getWithPromotions($country)
    {

        return self::where('is_active', true)
            ->whereHas('promotions')
            ->with([
                'promotions' => function ($query) use ($country){
                    $query->select(['title', 'image', 'desktop_image', 'country', 'body', 'start_date', 'end_date'])
                        ->where('status', PromotionConstants::STATUS_VISIBLE)
                        ->where('country', $country)
                        ->where(function (Builder $query) {
                            $query->whereNotNull('start_date')
                                    ->whereNotNull('end_date')
                                    ->whereColumn('start_date', '<=', 'end_date')
                                    ->Where('end_date', '>=', now());
                        })->orderBy('pivot_promotion_sort_order');
                }
            ]);
    }
}
