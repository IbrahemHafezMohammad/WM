<?php

namespace App\Models;

use App\Constants\AgentConstants;
use App\Constants\UserConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Agent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'senior_agent_id',
        'unique_code',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seniorAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'senior_agent_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function agentChangeHistoriesForPrevious(): HasMany
    {
        return $this->hasMany(AgentChangeHistory::class, 'previous_agent_id');
    }

    public function agentChangeHistoriesForNew(): HasMany
    {
        return $this->hasMany(AgentChangeHistory::class, 'new_agent_id');
    }

    //custom function
    public static function checkAgentUniqueCode($unique_code)
    {
        $agent = Agent::firstWhere('unique_code', $unique_code);

        return (!$agent || !$agent->user) ? null : $agent;
    }

    public static function getAgentsWithRelations($searchParams, bool $normal)
    {
        $query = Agent::with([
            'user:id,name,user_name,phone',
            'user.signupHistory',
            'user.latestLoginHistory',
            'seniorAgent.user:id,name,user_name'
        ]);

        $normal ? $query->whereHas('seniorAgent') : $query->whereDoesntHave('seniorAgent');

        if (array_key_exists('search', $searchParams)) {

            $query->searchUsernameOrUniqueCode($searchParams['search']);
        }

        return $query;
    }

    public function scopeSearchUsernameOrUniqueCode($query, $search)
    {
        $query->whereHas('user', function (Builder $query) use ($search) {

            $query->where(AgentConstants::TABLE_NAME . '.unique_code', 'like', '%' . $search . '%')
                ->orWhere(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $search . '%');
        });
    }
}