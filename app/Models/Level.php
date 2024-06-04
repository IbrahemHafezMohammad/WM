<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Constants\LevelConstants;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $table = LevelConstants::TABLE_NAME;
    
    protected $fillable = [
        'id',
        'level',
        'level_name',
        'min',
        'max'
    ];
}
