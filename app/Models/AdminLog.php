<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    use HasFactory;
    protected $with = ['admin:id,name'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
