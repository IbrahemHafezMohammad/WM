<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Tests\Integration\Database\Decimal;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'base_balance',
        'currency',
        'locked_base_balance'
    ];

    protected $appends = ['currency_name', 'balance', 'locked_balance'];

    public function getBalanceAttribute()
    {
        return round($this->base_balance / GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
    }

    public function getLockedBalanceAttribute()
    {
        return is_null($this->locked_base_balance) ? null : round($this->locked_base_balance / GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
    }

    public function getCurrencyNameAttribute()
    {
        return GlobalConstants::getCurrency($this->currency);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    // custom functions

    /**
     * @param $amount float
     * @return bool
     * lock balance for withdraw transaction
     */

    public function lockBalance(float $amount)
    {
        $this->locked_base_balance += round($amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
        return $this->save();
    }

    public function debit(float $amount)
    {
        $this->base_balance -= round($amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
        return $this->save();
    }

    public function credit(float $amount)
    {
        $this->base_balance += round($amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
        return $this->save();
    }

    public function baseCredit(float $baseAmount){
        $this->base_balance += $baseAmount;
        return $this->save();
    }

    public function removeLockedBalance(float $amount)
    {
        $this->locked_base_balance -= round($amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
        return $this->save();
    }

    public function adjustBalance(float $amount)
    {
        $this->base_balance = round($amount * GlobalConstants::getConversionRate($this->currency), GlobalConstants::DECIMAL_PRECISION);
        return $this->save();
    }
}
