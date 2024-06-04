<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderIPWhitelist extends Model
{
    use HasFactory;

    protected $table = "providers_ip_whitelists";

    protected $fillable = [
        'provider_code',
        'ip_address',
    ];
}
