<?php

namespace App\Http\Controllers;

use App\Constants\TransactionConstants;
use App\Models\Player;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    //in request, get one of these values for timeframe "today, yesterday, this_week, last_week, this_month, last_month," and return the total deposits for the given timeframe
    public function getDashboardStats(Request $request)
    {
        $timeframe = $request->timeframe;
        $transactions = Transaction::where('status', TransactionConstants::STATUS_APPROVED);
        $transactions = $this->attachTimeFrameToQuery($transactions, $timeframe);
        $deposits = clone $transactions;
        $withdraws = clone $transactions;
        //for first deposits, we need to get the first deposit for each user, so we group by user_id and get the first deposit for each user
        $firstDeposits = Transaction::latest()->where('status', TransactionConstants::STATUS_APPROVED)->groupBy('player_id');
        $firstDeposits = $this->attachTimeFrameToQuery($firstDeposits, $timeframe);

        $totalSignups = User::whereHas('player')->latest();
        $totalPlayers  = clone $totalSignups;
        $totalSignups = $this->attachTimeFrameToQuery($totalSignups, $timeframe);

        return response()->json([
            'status' => true,
            'message' => 'DEPOSITS_FETCHED_SUCCESSFULLY',
            'deposits' => $deposits->where('isWithdraw', false)->sum('amount'),
            'withdraws' => $withdraws->where('isWithdraw', true)->sum('amount'),
            'first_deposits' => $firstDeposits->count(),
            'total_signups' => $totalSignups->count(),
            'total_players' => $totalPlayers->count()
        ], 200);
    }

    public function getMonthlyTransaction()
    {
        return response()->json([
            'status' => true,
            'name_of_months' => Transaction::getNameOfMonths(),
            'transaction_data' => Transaction::getMonthlyTransactionsData()
        ]);
    }

    public function getDailyCurrentMonthTransaction()
    {
        return response()->json([
            'status' => true,
            'transaction_data' => Transaction::getDailyCurrentMonthTransactionsData()
        ]);
    }

    //function to return chart data of deposit and withdraw transactions based on given timeframe
    protected function attachTimeFrameToQuery($query, $timeframe){
        switch ($timeframe) {
            case "today":
                $query->whereDate('created_at',  Carbon::today());
                break;
            case "yesterday":
                $query->whereDate('created_at', Carbon::yesterday());
                break;
            case "this_week":
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case "last_week":
                $query->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                break;
            case "this_month":
                $query->whereMonth('created_at', Carbon::now()->month);
                break;
            case "last_month":
                $query->whereMonth('created_at', Carbon::now()->subMonth()->month);
                break;
            default:
                break;
        }
        return $query;
    }



}
