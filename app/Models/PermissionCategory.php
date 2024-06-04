<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Permission;

class PermissionCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function Permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

}