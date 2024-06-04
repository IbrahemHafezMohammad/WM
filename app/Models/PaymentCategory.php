<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PaymentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'public_name',
        'is_enabled',
    ];

    protected $casts = [
        'public_name' => 'array',
    ];

    protected $appends = ['full_image'];

    public function getFullImageAttribute()
    {
        $iconData = null;
        if ($this->icon) {
            foreach ($this->icon as $key => $path) {
                if ($path) {
                    $iconData[$key] = Storage::url($path);
                }
            }
        }
        return $iconData;
    }



    protected function icon(): Attribute
    {
        return new Attribute(
            set: function ($value) {
                return json_encode($value);
            },
            get: function ($value) {
                return json_decode($value, true);
            }
        );
    }

    // //relations

    public function userPaymentMethods()
    {
        return $this->hasMany(UserPaymentMethod::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function bankCodes()
    {
        return $this->hasMany(BankCode::class);
    }

    public function deleteENIcon()
    {
        if ($this->icon['en'] ?? null) {
            return Storage::delete(substr($this->icon['en'], strpos($this->icon['en'], GlobalConstants::PAYMENT_CATEGORY_PATH)));
        }
        return true;
    }

    public function deleteVNIcon()
    {
        if ($this->icon['vn'] ?? null) {
            return Storage::delete(substr($this->icon['vn'], strpos($this->icon['vn'], GlobalConstants::PAYMENT_CATEGORY_PATH)));
        }
        return true;
    }

    public function deleteTLIcon()
    {
        if ($this->icon['tl'] ?? null) {
            return Storage::delete(substr($this->icon['tl'], strpos($this->icon['tl'], GlobalConstants::PAYMENT_CATEGORY_PATH)));
        }
        return true;
    }

    public function deleteHIIcon()
    {
        if ($this->icon['hi'] ?? null) {
            return Storage::delete(substr($this->icon['hi'], strpos($this->icon['hi'], GlobalConstants::PAYMENT_CATEGORY_PATH)));
        }
        return true;
    }

    //custom function

    public static function relatedBankCodes()
    {
        return PaymentCategory::where('is_enabled', true)->with(['bankCodes'  => function ($query) {
            $query->where('status', true)->where('display_for_players', true);
        }])->whereHas('bankCodes', function ($query) {
            $query->where('status', true)->where('display_for_players', true);
        })->orderBy('id', 'desc')->get();
    }

    public static function relatedPaymentMethods($currency)
    {
        return PaymentCategory::where('is_enabled', true)->with(['paymentMethods' => function ($query) use ($currency) {
            $query->where('allow_deposit', true)->where('under_maintenance', false)->where('currency', $currency)->with(['bankCode' => function ($query) {
                $query->where('status', true)->where('display_for_players', true);
            }])->whereHas('bankCode', function ($query) {
                $query->where('status', true)->where('display_for_players', true);
            });
        }])->whereHas('paymentMethods', function ($query) use ($currency) {
            $query->where('allow_deposit', true)->where('under_maintenance', false)->where('currency', $currency)
                ->whereHas('bankCode', function ($query) {
                    $query->where('status', true)->where('display_for_players', true);
                });
        })->orderBy('id', 'desc')->get();
    }
}
