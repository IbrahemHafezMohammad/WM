<?php

namespace App\Http\Controllers;

use App\Models\BetRound;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListBetRoundsRequest;

class BetRoundController extends Controller
{
    public function index(ListBetRoundsRequest $request)
    {
        $records_query = BetRound::getBetRoundsWithRelations($request->validated());

        $sums_query = clone $records_query;

        $sums = $sums_query->select(
            'currency',
            DB::raw('SUM(total_valid_bets) as total_valid_bets_sum'),
            DB::raw('SUM(total_turnovers) as total_turnovers_sum'),
            DB::raw('SUM(total_win_amount) as total_win_amount_sum'),
            DB::raw('SUM(win_loss) as win_loss_sum')
        )
            ->groupBy('currency')
            ->get();

        $records_query->withSum([
            'bets' => function ($query) {
                $query->select(DB::raw('SUM(bet_amount)'));
            }
        ], 'total_bet_amount');

        $records_query->with([
            'player',
            'gamePlatform',
            'player.agent:id,unique_code',
        ]);

        $records = $records_query->orderByDesc('id')->paginate($request->validated()['per_page']);

        return response()->json([
            'status' => true,
            'records' => $records,
            'sums' => $sums,
        ]);
    }

    public function Bettings(Request $request)
    {
        $params = [
            'player_id' => $request->player_id,
            'per_page' => $request->per_page,
            'provider' => $request->provider,
            'from_date' => $request->from_date ?? null,
            'to_date' => $request->to_date ?? null

        ];
        return BetRound::getBetRounds($params);
    }

    public function view(BetRound $bet_round)
    {
        return $bet_round->getDetails();
    }

    public function getMonthWinLoss()
    {
        return response()->json([
            'status' => true,
            'total_winloss' => BetRound::getMonthWinloss(),
        ]);
    }
}
