<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhitelistIP extends Model
{
    use HasFactory;
    protected $fillable =['name','ip','type'];

    //search scope for whitelist ip listing page
    public static function scopeSearch($searchData) : Builder
    {
        $query = self::query();
        if (array_key_exists('ip', $searchData)) {
            $query->where('ip', 'like', '%' . $searchData['ip'] . '%');
        }
        if (array_key_exists('name', $searchData)) {
            $query->where('name', 'like', '%' . $searchData['name'] . '%');
        }
        if (array_key_exists('type', $searchData)) {
            switch ($searchData['type']){
                case 1:
                    $query->where('type',  1);
                    break;
                case 2:
                    $query->where('type', 2);
                    break;
                default:
            }
        }
        return $query;
    }

}
