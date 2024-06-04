<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerNotification extends Model
{
    protected $fillable = ['notification_id', 'player_id', 'is_read'];

    public function notification()
    {
        return $this->belongsTo(Notification::class,'notification_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class,'player_id');
    }

    public static function getPlayerNotifications($playerId)
    {
        return Player::Select("id")->without(['wallet', 'user'])
            ->with(['playerNotifications' => function ($query) {
                $query->orderBy('created_at','desc')->paginate(10);
            }, 'playerNotifications.notification'])
            ->where('id', $playerId)->first();
            
    }

    public static function getNotificationsData()
    {
        return Player::Select("id")->without(['wallet', 'user'])
            ->with(['playerNotifications' => function ($query) {
                $query->orderBy('created_at','desc')->paginate(10);
            }, 'playerNotifications.notification'])
           ->get();
            
    }

    public static function getPlayerNotificationsCount($playerId)
    {
        return self::where('player_id', $playerId)
            ->where('is_read', false)
            ->count();
    }

    public function markAsRead($playerId)
    {
        $this->where('player_id', $playerId)->update(['is_read' => true]);
    }
}

