<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListPlayerBalanceHistoriesRequest;
use App\Models\PlayerBalanceHistory;

class PlayerBalanceHistoryController extends Controller
{
    public function index(ListPlayerBalanceHistoriesRequest $request)
    {
        $query = PlayerBalanceHistory::getPlayerBalanceHistory($request->validated());

        $total = clone $query;
        $withdraw_query = clone $query;
        $deposit_query = clone $query;

        return [
            'player_balance_history' => $query->orderByDesc('id')->paginate(10),
            'total' => $total->sum('amount'),
            'withdraw_total' => $withdraw_query->where('is_deduction', false)->sum('amount'),
            'deposit_total' => $deposit_query->where('is_deduction', true)->sum('amount')
        ];

    }
}