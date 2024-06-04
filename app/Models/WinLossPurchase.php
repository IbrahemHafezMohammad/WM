<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WinLossPurchase extends Model
{
    use HasFactory;

    protected $fillable = ['amount', 'currency','purchased_by'];

    protected $appends = ['base_amount', 'currency_name'];

    public function getBaseAmountAttribute()
    {
        return $this->amount ? round($this->amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getCurrencyNameAttribute()
    {
        return GlobalConstants::getCurrency($this->currency);
    }

    public function purchaser()
    {
        return $this->belongsTo(Admin::class, 'purchased_by');
    }
}
