<?php

namespace App\Models;

use App\Constants\BankCodeConstants;
use App\Constants\GlobalConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'image',
        'public_name',
        'display_for_players',
        'payment_category_id',
        'status',
    ];

    protected $appends = [
        'code_name',
        'full_image'
    ];

    protected $casts = [
        'public_name' => 'array'
    ];

    public function getCodeNameAttribute()
    {
        return BankCodeConstants::getCode($this->code);
    }

    //relations
    // protected function image(): Attribute
    // {
    //     return new Attribute(
    //         get: fn($value) => $value ? Storage::url($value) : $value
    //     );
    // }

    public function getFullImageAttribute() {
        return $this->image ? Storage::url($this->image) : null;
    }

    public function paymentCategory()
    {
        return $this->belongsTo(PaymentCategory::class);
    }
    
    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function userPaymentMethods()
    {
        return $this->hasMany(UserPaymentMethod::class);
    }

    //custom function

    public function deleteImage()
    {
        if ($this->image) {
            return Storage::delete(substr($this->image, strpos($this->image, GlobalConstants::BANK_CODE_IMAGES_PATH)));
        }
        return true;
    }
}
