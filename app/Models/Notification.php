<?php

namespace App\Models;

use App\Constants\NotificationConstants;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Notification extends Model
{
    public $timestamps = false;

    protected $table = NotificationConstants::TABLE_NAME;
    protected $fillable = ['title', 'description', 'created_by'];

    public function playerNotifications()
    {
        return $this->hasMany(PlayerNotification::class,'notification_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public static function getNotificationByTitle($title)
    {
        $Notificationfound = static::where('title', $title)->first();
        return $Notificationfound ?? false;
    }

    public static function createNotification(array $data, array $players)
    {


        $data['created_by'] = Auth::user()->id;

        $createdNotification  = static::create($data);
        $id = $createdNotification->id;
        $res = static::createPlayerNotification($id, $players);

        return $res;
    }

    public static function createPlayerNotification($notification_id, $playerIds)
    {
        $createdPlayerNotificationIds = [];
        foreach ($playerIds as $playerId) {
            $createdPlayerNotification = PlayerNotification::create([
                'notification_id' => $notification_id,
                'player_id' => $playerId,
                'is_read' => false,
                'created_at' => now(),
            ]);
            array_push($createdPlayerNotificationIds, $createdPlayerNotification->id);
        }
        return [
            'createdPlayerNotificationIds' => $createdPlayerNotificationIds,
            'NotificationId' => $notification_id
        ];
    }

    public static function getAllNotifications($searchParams)
    {
        $notifications = Notification::with([
            'createdBy:id,user_id',
            'createdBy.user:id,user_name', // Load user data for createdBy relationship
            'playerNotifications.player' => function ($query) {
                $query->select('id','user_id');
            },
            'playerNotifications.player.user:id,user_name'

        ])
        ->when(isset($searchParams['title']), function ($query) use ($searchParams) {
            return $query->where('title', $searchParams['title']);
        })
        ->when(isset($searchParams['from_date']), function ($query) use ($searchParams) {
            return $query->where('created_at', '>=', $searchParams['from_date']);
        })
        ->orderBy('id', 'desc')
        ->paginate(10);
        
        return $notifications;
        
        
    }
}
