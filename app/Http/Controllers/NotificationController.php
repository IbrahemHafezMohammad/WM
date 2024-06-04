<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Player;
use App\Models\PlayerNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        try {

            $params = [
                'title' => $request->title ?? null,
                'from_date' => $request->from_date ?? null,
                'to_date' => $request->to_date ?? null,
            ];

            $notifications = Notification::getAllNotifications($params);

            return response()->json([
                'status' => true,
                'data' => $notifications,
                'message' => 'Notifications retrieved successfully',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    public function create(Request $request)
    {

        $customMessages = [
            'players.required' => 'users array cannot be null choose atleast one user to Create Notification',
        ];

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'players' => 'required|array',
        ], $customMessages);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        try {

            $players = $request->players;
            $data = [
                'title' => $request->title,
                'description' => $request->description,
            ];

            // duplicates with title
            if (Notification::getNotificationByTitle($data['title'])) {
                return response()->json([
                    'status' => 409,
                    'message' => 'Notification with the same title already exists',
                ], 409);
            }

            $created_notification_with_users = Notification::createNotification($data, $players);

            if ($created_notification_with_users) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Notification created successfully',
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {

            $notification = Notification::with([
                'playerNotifications:notification_id,player_id',
                'playerNotifications.player:id,user_id',
                'playerNotifications.player.user:id,user_name',
                'playerNotifications.player.wallet:id'
            ])->findOrFail($id);

            return response()->json([
                'status' => true,
                'data' => [
                    'notification' => $notification,
                ],
                'message' => 'Notification retrieved successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found or Invaild Notification ID',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $notification = Notification::findOrFail($id);

            $notification->update([
                'title' => $request->title,
                'description' => $request->description,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Notification updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete(string $id)
    {
        try {
            $notification = Notification::findOrFail($id);
            $notification->delete();

            return response()->json([
                'status' => true,
                'message' => "Notification deleted successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPlayerNotifications($playerId)
    {
        $notificationsData = PlayerNotification::getPlayerNotifications($playerId);
        return response()->json([
            'status' => true,
            'data'=>$notificationsData ?? [],
            'message' => "Notification Fetched successfully",
        ], 200);
    }

    public function getPlayerNotificationsCount($playerId)
    {
        $count =  PlayerNotification::getPlayerNotificationsCount($playerId);
        return response()->json([
            'status' => true,
            'count' => $count,
        ], 200);
    }

    public function markPlayerNotificationsAsRead($playerId)
    {
        $notification = new PlayerNotification();
        $notification->markAsRead($playerId);
        return response()->json([
            'status' => true,
            'message' => "Notification set markAsRead successfully",
        ], 200);
    }
    
    public function getPlayer(Request $request)
    {
        $query = Player::with(['user' => function ($query) {
            $query->select(['id', 'user_name', 'phone']);
        }])->without(('wallet'))
        ->select('players.id', 'players.user_id');  
    
        if ($request->has('username')) {
            $username = $request->input('username');
            // Adjust the query to search by username
            $query->whereHas('user', function ($query) use ($username) {
                $query->where('user_name', 'like', '%' . $username . '%');
            });
    
            // Return all matching players without pagination
            $players = $query->get();
        } 
        
        else {
            // Otherwise, return the default 100 players ordered by players.id
            $players = $query->orderBy('players.id')->get();
        }
    
        return response()->json($players);
    }
    
}
