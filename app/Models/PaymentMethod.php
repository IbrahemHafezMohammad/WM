<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use Illuminate\Database\Eloquent\Model;
use App\Services\LogService\AdminLogService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Services\PaymentService\PaymentServiceEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_code_id',
        'payment_category_id',
        'account_name',
        'account_number',
        'public_name',
        'bank_city',
        'bank_branch',
        'allow_deposit',
        'allow_withdraw',
        'under_maintenance',
        'api_key',
        'callback_key',
        'api_url',
        'remark',
        'balance',
        'max_daily_amount',
        'max_total_amount',
        'min_deposit_amount',
        'max_deposit_amount',
        'min_withdraw_amount',
        'max_withdraw_amount',
        'currency',
        'payment_code',
        'internal_name',
        'is_default'
    ];

    protected $appends = [
        'base_balance',
        'base_max_daily_amount',
        'base_max_total_amount',
        'base_min_deposit_amount',
        'base_max_deposit_amount',
        'base_min_withdraw_amount',
        'base_max_withdraw_amount',
        'currency_name',
        'payment_code_name',
        'en_public_name',
        'vn_public_name',
        'tl_public_name',
        'hi_public_name',
    ];

    protected $casts = [
        'public_name' => 'array',
    ];

    public function getEnPublicNameAttribute()
    {
        if (is_null($this->public_name)) {
            return null;
        }
        return $this->public_name['en_public_name'] ?? null;
    }

    public function getVnPublicNameAttribute()
    {
        if (is_null($this->public_name)) {
            return null;
        }
        return $this->public_name['vn_public_name'] ?? null;
    }

    public function getHiPublicNameAttribute()
    {
        if (is_null($this->public_name)) {
            return null;
        }
        return $this->public_name['tl_public_name'] ?? null;
    }

    public function getTlPublicNameAttribute()
    {
        if (is_null($this->public_name)) {
            return null;
        }
        return $this->public_name['hi_public_name'] ?? null;
    }

    public function getBaseBalanceAttribute()
    {
        return $this->balance ? round($this->balance * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION) : null;
    }

    public function getBaseMaxDailyAmountAttribute()
    {
        if (is_null($this->max_daily_amount)) {
            return null;
        }
        return round($this->max_daily_amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
    }

    public function getBaseMaxTotalAmountAttribute()
    {
        if (is_null($this->max_total_amount)) {
            return null;
        }
        return round($this->max_total_amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
    }

    public function getBaseMinDepositAmountAttribute()
    {
        if (is_null($this->min_deposit_amount)) {
            return null;
        }
        return round($this->min_deposit_amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
    }

    public function getBaseMaxDepositAmountAttribute()
    {
        if (is_null($this->max_deposit_amount)) {
            return null;
        }
        return round($this->max_deposit_amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
    }

    public function getBaseMinWithdrawAmountAttribute()
    {
        if (is_null($this->min_withdraw_amount)) {
            return null;
        }
        return round($this->min_withdraw_amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
    }

    public function getBaseMaxWithdrawAmountAttribute()
    {
        if (is_null($this->max_withdraw_amount)) {
            return null;
        }
        return round($this->max_withdraw_amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
    }

    public function getPaymentCodeNameAttribute()
    {
        return PaymentServiceEnum::tryFrom($this->payment_code)?->name;
    }

    public function getCurrencyNameAttribute()
    {
        return GlobalConstants::getCurrency($this->currency);
    }


    //relations
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function bankCode(): BelongsTo
    {
        return $this->belongsTo(BankCode::class);
    }

    public function paymentCategory(): BelongsTo
    {
        return $this->belongsTo(PaymentCategory::class);
    }
    
    // custom function 

    public function adjustBalance($isWithdraw, $amount)
    {
        $old_balance = $this->balance;

        if ($isWithdraw) {

            if ($amount > $this->balance) {

                return null;
            }
            $this->balance -= $amount;
            AdminLogService::createLog('Payment Method ' . $this->public_name . ' balance is Deducted to  : ' . $this->balance);

        } else {
            $this->balance += $amount;
            AdminLogService::createLog('Payment Method ' . $this->public_name . ' balance is Added to  : ' . $this->balance);
        }
        $this->save();
        return $old_balance;
    }

    public static function getDepositBanks($currency)
    {
        return self::where('allow_deposit', true)->where('currency', $currency);
    }
}
