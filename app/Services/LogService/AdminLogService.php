<?php

namespace App\Services\LogService;

use App\Models\AdminLog;
use App\Services\WebService\WebRequestService;
use Illuminate\Support\Facades\Auth;

class AdminLogService
{

    public static function createLog($change, $userId = null, $ip = null)
    {
        $web_request_service = new WebRequestService(request());
        $log = new AdminLog();
        $log->change = $change;
        $log->actor_id = $userId ?? Auth::id();
        $log->ip = $ip ?? $web_request_service->getIpAddress();
        $log->save();
    }
}
