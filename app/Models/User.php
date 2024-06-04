<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Constants\GlobalConstants;
use App\Constants\UserConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'user_name',
        'password',
        'phone',
        'remark',
        'profile_pic',
        'gender',
        'birthday',
    ];

    protected $attributes = [
        'gender' => UserConstants::GENDER_UNKNOWN
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    //accessors and mutators
    protected function profilePic(): Attribute
    {
        return new Attribute(
            get: fn($value) => $value ? Storage::url($value) : $value
        );
    }

    protected function password(): Attribute
    {
        return new Attribute(
            set: fn($value) => Hash::make($value)
        );
    }

    //relations
    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    public function player(): HasOne
    {
        return $this->hasOne(Player::class);
    }

    public function loginHistory(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function signupHistory()
    {
        return $this->hasOne(LoginHistory::class)->oldestOfMany();
    }

    public function latestLoginHistory()
    {
        return $this->hasOne(LoginHistory::class)->latestOfMany();
    }

    public function userPaymentMethods(): HasMany
    {
        return $this->hasMany(UserPaymentMethod::class);
    }

    public function activeUserPaymentMethods(): HasMany
    {
        return $this->userPaymentMethods()->where('is_active', true);
    }

    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    public function processingTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'processing_by');
    }

    public function approvedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'approved_by');
    }

    public function gameTransactionHistories(): HasMany
    {
        return $this->hasMany(GameTransactionHistory::class, 'changed_by');
    }

    public function promotion(): HasMany
    {
        return $this->hasMany(Promotion::class, 'turned_on_by');
    }

    public function assessTransactionRisks(): HasMany
    {
        return $this->hasMany(Transaction::class, 'risk_action_by');
    }

    public function gameCategory(): HasMany
    {
        return $this->hasMany(GameCategory::class, 'updated_by');
    }

    public function playerBalanceHistories(): HasMany
    {
        return $this->hasMany(PlayerBalanceHistory::class, 'action_by');
    }

    //custom functions
    public static function checkPlayerUserName($user_name)
    {
        $user = self::firstWhere('user_name', $user_name);

        return (!$user || !$user->player) ? null : $user;
    }

    public static function checkAdminUserName($user_name)
    {
        $user = self::firstWhere('user_name', $user_name);

        return (!$user || !$user->admin) ? null : $user;
    }

    public static function checkAgentUserName($user_name)
    {
        $user = self::firstWhere('user_name', $user_name);

        return (!$user || !$user->agent) ? null : $user;
    }

    public function verifyPassword($password)
    {
        return Hash::check($password, $this->password);
    }

    public function uploadProfilePicture($image)
    {
        if (!is_null($image)) {
            $this->profile_pic = Storage::putFile(GlobalConstants::USER_IMAGES_PATH, $image);
        }

        return $this;

    }
}
