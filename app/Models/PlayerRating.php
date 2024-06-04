<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Database\Eloquent\Builder as EagerBuilder;

class PlayerRating extends Model
{
    use HasFactory;

    protected $fillable = [
       'comment',
       'created_by',
       'department',
       'rating'
    ];

    //relations
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class , 'created_by');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public static function getPlayerRatingData()
    {
        return PlayerRating::with(['admin' => function (EagerBuilder $query) {
            $query->with(['user:id,user_name'])->select(['id', 'user_id']);
        }]);
    }
}
