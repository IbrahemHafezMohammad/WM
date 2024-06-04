<?php

namespace App\Models;

use App\Constants\GlobalConstants;
use App\Constants\PlayerConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_code_id',
        'payment_category_id',
        'user_id',
        'account_name',
        'account_number',
        'bank_city',
        'bank_branch',
        'is_active',
        'remark',
        'currency',
    ];

    protected $appends = [
        'currency_name',
    ];

    public function getCurrencyNameAttribute()
    {
        return GlobalConstants::getCurrency($this->currency);
    }

    // relations

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // scopes
    public static function scopePlayerPaymentMethods($query, $player_id)
    {
        $query->whereHas('user.player', function ($query) use ($player_id) {

            $query->where(PlayerConstants::TABLE_NAME . '.id', $player_id);
        });
    }
}
