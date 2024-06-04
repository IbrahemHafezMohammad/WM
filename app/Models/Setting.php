<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key' ,
        'value'
    ];

    public static function getSettings($searchParams){
        $query = Setting::query();

        if (array_key_exists('search', $searchParams)) {
            $query->searchKeyOrValue($searchParams['search']);
        }

        return $query;
    }

    public function scopeSearchKeyOrValue($query, $value)
    {
        $query->where('key', 'like', '%' . $value . '%')
            ->orWhere('value', 'like', '%'. $value. '%');
    }

}
