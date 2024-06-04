<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListGameTransactionHistoriesRequest;
use App\Models\GameTransactionHistory;

class GameTransactionHistoryController extends Controller
{
    public function index(ListGameTransactionHistoriesRequest $request)
    {
        return GameTransactionHistory::getGameTransactionHistoriesWithRelations($request->validated())->orderByDesc('id')->paginate(10);
    }
}