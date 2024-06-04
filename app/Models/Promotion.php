<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\DB;
use App\Constants\PromotionConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Constants\PromotionCategoryConstants;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Constants\PromotionPromotionCategoryConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'status',
        'country',
        'image',
        'desktop_image',
        'body',
        'start_date',
        'end_date',
        'turned_on_by',
    ];

    protected $appends = [
        'country_name',
        'full_image',
        'full_desktop_image'
    ];

    public function getCountryNameAttribute()
    {
        return GlobalConstants::getCountry($this->country);
    }

    public function getFullImageAttribute()
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    public function getFullDesktopImageAttribute()
    {
        return $this->desktop_image ? Storage::url($this->desktop_image) : null;
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'turned_on_by');
    }

    public function promotionCategories(): BelongsToMany
    {
        return $this->belongsToMany(PromotionCategory::class, PromotionPromotionCategoryConstants::TABLE_NAME)->withPivot('promotion_sort_order');
    }

    //custom function
    public function deleteImage()
    {
        if ($this->image ?? null) {
            return Storage::delete(substr($this->image, strpos($this->image, GlobalConstants::PROMOTIONS_IMAGES_PATH)));
        }
        return true;
    }

    public function deleteDesktopImage()
    {
        if ($this->desktop_image ?? null) {
            return Storage::delete(substr($this->desktop_image, strpos($this->desktop_image, GlobalConstants::PROMOTIONS_IMAGES_PATH)));
        }
        return true;
    }

    public function deleteAllImages()
    {
        return $this->deleteImage() &&
            $this->deleteDesktopImage();
    }

    public function toggleStatus($turned_on_by_id)
    {
        return $this->update([
            'status' => !$this->status,
            'turned_on_by' => $turned_on_by_id
        ]);
    }

    public function updatePromotionCategories($promotion_category_ids)
    {
        foreach ($promotion_category_ids as $category_id) {
            $this->promotionCategories()->syncWithoutDetaching([$category_id => ['promotion_sort_order' => 0]]);
        }

        $current_category_ids = $this->promotionCategories()->pluck(PromotionCategoryConstants::TABLE_NAME . '.id')->toArray();
        $categories_to_detach = array_diff($current_category_ids, $promotion_category_ids);
        $this->promotionCategories()->detach($categories_to_detach);
    }
}
