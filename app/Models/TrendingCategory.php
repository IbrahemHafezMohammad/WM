<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TrendingCategory extends Model
{
    protected $table = 'trending_category';

    protected $fillable = [
        'game_category_id', 
        'status', 
        'sort_order', 
        'active_image', 
        'inactive_image'
    ];

    public function gameCategory()
    {
        return $this->belongsTo(GameCategory::class, 'game_category_id'); // Foreign key and the related model
    }
}

