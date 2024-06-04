<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListAdminLoginHistoryRequest;
use App\Http\Requests\ListPlayerLoginHistoryRequest;
use App\Models\LoginHistory;

class LoginHistoryController extends Controller
{

    public function listPlayersLoginHistory(ListPlayerLoginHistoryRequest $request)
    {
        return LoginHistory::getPlayerLoginHistory($request->validated())->paginate(10);
    }

    public function listAdminsLoginHistory(ListAdminLoginHistoryRequest $request)
    {
        return LoginHistory::getAdminLoginHistory($request->validated())->paginate(10);
    }
}