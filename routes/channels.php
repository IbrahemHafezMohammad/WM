<?php

use App\Constants\GlobalConstants;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel(GlobalConstants::TRANSACTIONS_BROADCAST_CHANEL_NAME, function (User $user) {

    return true;
});

// Broadcast::channel(GlobalConstants::GAME_TRANSACTIONS_BROADCAST_CHANEL_NAME . '{id}', function (User $user, $id) {

//     return $user->player && (int) $user->player->id === (int) $id;;
// });