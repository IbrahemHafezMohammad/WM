<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListGameAccessHistoriesRequest;
use App\Models\GameAccessHistory;


class GameAccessHistoryController extends Controller
{
    public function index(ListGameAccessHistoriesRequest $request)
    {
        return GameAccessHistory::getGameAccessHistoriesWithRelations($request->validated())->orderByDesc('id')->paginate(10);
    }
}