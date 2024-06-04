<?php

namespace App\Models;

use App\Constants\AgentChangeHistoryConstants;
use App\Constants\UserConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AgentChangeHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'previous_agent_id',
        'new_agent_id',
        'change_by'
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function previousAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'previous_agent_id');
    }

    public function newAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'new_agent_id');
    }

    public function changeBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'change_by');
    }

    //custom function
    public static function getAgentHistoriesWithRelations($searchParams)
    {
        $query = AgentChangeHistory::with([
            'player.user:id,user_name',
            'previousAgent.user:id,user_name',
            'newAgent.user:id,user_name',
            'changeBy.user:id,user_name'
        ]);


        if (array_key_exists('search', $searchParams)) {

            $query->userNames($searchParams['search']);
        }

        if (array_key_exists('start_date', $searchParams)) {

            $query->startDate($searchParams['start_date']);
        }

        if (array_key_exists('end_date', $searchParams)) {

            $query->endDate($searchParams['end_date']);
        }

        return $query;
    }

    public function scopeUserNames($query, $user_name)
    {
        $query->whereHas('player.user', function (Builder $query) use ($user_name) {

            $query->where(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $user_name . '%');

        })->orWhereHas('previousAgent.user', function (Builder $query) use ($user_name) {

            $query->where(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $user_name . '%');

        })->orWhereHas('newAgent.user', function (Builder $query) use ($user_name) {

            $query->where(UserConstants::TABLE_NAME . '.user_name', 'like', '%' . $user_name . '%');

        });
    }

    public function scopeStartDate($query, $date)
    {
        $query->where(AgentChangeHistoryConstants::TABLE_NAME . '.created_at', '>=', $date);
    }

    public function scopeEndDate($query, $date)
    {
        $query->where(AgentChangeHistoryConstants::TABLE_NAME . '.created_at', '<=', $date);
    }
}