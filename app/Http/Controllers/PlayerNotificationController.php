<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCreatePlayerNotificationRequest;
use App\Http\Requests\AdminDeletePlayersNotificationsRequest;
use App\Http\Requests\AdminListPlayersNotificationsRequest;
use App\Http\Requests\AdminUpdatePlayerNotificationRequest;
use App\Models\PlayerNotification;

class PlayerNotificationController extends Controller
{
    public function create(AdminCreatePlayerNotificationRequest $request)
    {
        PlayerNotification::create($request->getPlayerNotificationData());

        return response()->json([
            'status' => true,
            'message' => 'PLAYER_NOTIFICATION_CREATED_SUCCESSFULLY',
        ], 200);
    }

    public function update(AdminUpdatePlayerNotificationRequest $request, PlayerNotification $notification)
    {
        if ($notification->update($request->getData())) {

            return response()->json([
                'status' => true,
                'message' => 'PLAYER_NOTIFICATION_UPDATED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PLAYER_NOTIFICATION_UPDATE_FAILED',
        ], 400);
    }

    public function index()
    {
        return PlayerNotification::getNotificationsData();
    }

    public function deleteAllRead()
    {
        if (PlayerNotification::readNotifications()->delete()) {

            return response()->json([
                'status' => true,
                'message' => 'PLAYER_NOTIFICATION_DELETED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'NO_READ_PLAYER_NOTIFICATION',
        ], 200);
    }

    public function deletePlayerRead(AdminDeletePlayersNotificationsRequest $request)
    {
        if (PlayerNotification::playerReadNotifications($request->validated()['player_id'])->delete()) {

            return response()->json([
                'status' => true,
                'message' => 'PLAYER_NOTIFICATION_DELETED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PLAYER_NOTIFICATION_DELETE_FAILED',
        ], 400);
    }
}