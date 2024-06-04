<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListAgentsRequest;
use App\Http\Requests\LoginAgentRequest;
use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Models\Agent;
use App\Models\User;
use App\Services\LogService\AdminLogService;
use App\Services\WebService\WebRequestService;
use Illuminate\Http\Request;
use App\Http\Requests\ResetAgentPasswordRequest;
use App\Constants\LoginHistoryConstants;
use App\Models\BetRound;
use App\Models\Deposit;
use App\Models\Player;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{

    const TODAY         = 'today';
    const THIS_WEEK     = 'this_week';
    const LAST_WEEK     = 'last_week';
    const THIS_MONTH    = 'this_month';
    const LAST_MONTH    = 'last_month';

    public function passwordReset(ResetAgentPasswordRequest $request, Agent $agent)
    {
        if ($agent->user->update(['password' => $request->validated()['new_password']])) {
            AdminLogService::createLog('Agent ' . $agent->user_name . ' password changed');
            return response()->json([
                'status' => true,
                'message' => 'PASSWORD_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PASSWORD_UPDATE_FAILED'
        ], 400);
    }

    public function index(Request $request)
    {
        $agents = Agent::with([
            'user:id,name,user_name,phone',
            'user.signupHistory',
            'user.latestLoginHistory',
            'seniorAgent.user:id,name,user_name'
        ]);

        if ($request->has("search")) {
            $agents->where(function ($query) use ($request) {
                $query->whereRelation('user', 'user_name', 'like', '%' . $request->search . '%')->orWhere("unique_code", 'like', '%' . $request->search . '%');
            });
        }
        if ($request->has("senior_agent")) {
            $agents->whereRelation("seniorAgent.user", 'user_name', 'like', '%' . $request->senior_agent . '%');
        }
        return $agents->orderByDesc('id')->get();
    }

    public function create(StoreAgentRequest $request)
    {
        $user = User::create($request->getUserData());

        $user->agent()->create($request->getAgentData());

        AdminLogService::createLog('New agent is added ' . $user->user_name . ' to the system');
        return response()->json([
            'status' => true,
            'message' => 'AGENT_CREATED_SUCCESSFULLY',
        ], 200);
    }

    public function update(UpdateAgentRequest $request, Agent $agent)
    {
        if ($agent->update($request->getAgentData()) && $agent->user->update($request->getUserData())) {
            AdminLogService::createLog('Agent ' . $agent->user->user_name . ' is updated');
            return response()->json([
                'status' => true,
                'message' => 'AGENT_UPDATED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'AGENT_UPDATE_FAILED',
        ], 400);
    }

    public function login(LoginAgentRequest $request)
    {
        $validated = $request->validated();

        // field name is unique_code because it's coming like that from the front but we use username logic
        if (!$user = User::checkAgentUserName($validated['unique_code'])) {
            return response()->json([
                'status' => false,
                'message' => 'USER_DOES_NOT_EXIST'
            ], 404);
        }

        $agent = $user->agent;

        if (!$user->verifyPassword($validated['password'])) {
            return response()->json([
                'status' => false,
                'message' => 'PASSWORD_INCORRECT'
            ], 403);
        }

        $user->tokens()->delete();

        $webrequestservice = new WebRequestService($request);
        $user->loginHistory()->create(['ip' => $webrequestservice->getIpAddress()]);

        return response()->json([
            'status'    =>  true,
            'message'   =>  'USER_LOGGED_IN_SUCCESSFULLY',
            'token'     =>  $user->createToken("API TOKEN")->plainTextToken,
            'user'      =>  $user
        ], 200);
    }

    public function listNormalAgents(ListAgentsRequest $request)
    {
        return Agent::getAgentsWithRelations($request->validated(), true)->orderByDesc('id')->paginate(10);
    }

    public function listSuperiorAgents(ListAgentsRequest $request)
    {
        return Agent::getAgentsWithRelations($request->validated(), false)->orderByDesc('id')->paginate(10);
    }

    public function destroy(Agent $agent)
    {
        $agent->delete();
        AdminLogService::createLog('Agent ' . $agent->user->user_name . ' is deleted');
        return response()->json([
            'status' => true,
            'message' => 'AGENT_DELETED_SUCCESSFULLY',
        ], 200);
    }

    public function getPlayersListedByAgent(Request $request)
    {
        $agent_id = Auth::user()->agent->id;

        // Initialize variables
        $startDate = null;
        $endDate = null;
        $username = null;

        // Parse request dates using Carbon
        if ($request && $request->startDate) {
            $startDate = Carbon::parse($request->startDate)->toDateString();
        }
        if ($request && $request->endDate) {
            // Set the end date to the end of the day
            $endDate = Carbon::parse($request->endDate)->endOfDay();
        }
        if ($request && $request->username) {
            $username   =   $request->username;
        }

        $playerData = DB::table('players')
        ->select(
            'players.id as player_id', 
            'players.user_id', 
            'users.name', 
            'users.user_name', 
            'summary.totalWithdrawApproved', 
            'summary.totalDepositApproved', 
            'users.created_at as registered_date', 
            'summary.lastDepositDate', 
            'lastLoginDate.lastLoginDate as lastLoginDate'
        )
        ->leftJoin('users', 'users.id', '=', 'players.user_id')
        ->leftJoin('login_histories', 'login_histories.user_id', '=', 'users.id')
        ->leftJoin(DB::raw('(SELECT user_id, MAX(created_at) as lastLoginDate FROM login_histories GROUP BY user_id) as lastLoginDate'), 'lastLoginDate.user_id', '=', 'users.id')
        ->leftJoin(DB::raw('(SELECT player_id,
                            SUM(CASE WHEN isWithdraw = 1 AND status = 1 THEN amount ELSE 0 END) as totalWithdrawApproved,
                            SUM(CASE WHEN isWithdraw = 0 AND status = 1 THEN amount ELSE 0 END) as totalDepositApproved,
                            MAX(CASE WHEN isWithdraw = 0 AND status = 1 THEN updated_at ELSE NULL END) as lastDepositDate
                            FROM transactions
                            GROUP BY player_id) as summary'), 'summary.player_id', '=', 'players.id')
        ->where('players.agent_id', $agent_id);
    
        if ($startDate && $startDate != null) {
            $playerData = $playerData->whereBetween('users.created_at', [$startDate, $endDate]);
        }
        
        if ($username && $username != null) {
            $playerData = $playerData->where('users.user_name', 'like', '%' . $username . '%');
        }
    
        $playerData = $playerData->groupBy(
                'players.id', 
                'players.user_id', 
                'users.name', 
                'users.user_name', 
                'users.created_at', 
                'summary.totalWithdrawApproved', 
                'summary.totalDepositApproved', 
                'summary.lastDepositDate', 
                'lastLoginDate.lastLoginDate'
            )
            ->orderBy('users.created_at', 'DESC')
            ->paginate(10);    


        return $playerData;
    }

    public function getPlayerTransactionsList(Request $request)
    {
        $agent_id = Auth::user()->agent->id;

        // Initialize variables
        $startDate  = null;
        $endDate    = null;
        $username   = null;
        $isWithdraw = null;

        // Parse request dates using Carbon
        if ($request && $request->startDate) {
            $startDate = Carbon::parse($request->startDate)->toDateString();
        }
        if ($request && $request->endDate) {
            // Set the end date to the end of the day
            $endDate = Carbon::parse($request->endDate)->endOfDay()->toDateString();
        }
        if ($request && $request->username) {
            $username   =   $request->username;
        }

        if ($request && $request->type && $request->type != "all") {
            $isWithdraw =   $request->type;
        }
        // Query for total withdrawal and deposit amounts
        $totalData = DB::table('players')
            ->select(
                DB::raw('SUM(CASE WHEN transactions.isWithdraw = 1 AND transactions.status = 1 THEN transactions.amount ELSE 0 END) as totalWithdrawApproved'),
                DB::raw('SUM(CASE WHEN transactions.isWithdraw = 0 AND transactions.status = 1 THEN transactions.amount ELSE 0 END) as totalDepositApproved')
            )
            ->join('transactions', 'players.id', '=', 'transactions.player_id')
            ->join('users', 'users.id', '=', 'players.user_id')
            ->where('players.agent_id', $agent_id);

        if ($startDate && $startDate != null) {
            $totalData = $totalData->whereBetween('users.created_at', [$startDate, $endDate]);
        }

        if ($username && $username != null) {
            $totalData = $totalData->where('users.user_name', 'like', '%' . $username . '%');
        }

        if ($isWithdraw && $isWithdraw != null) {
            if ($isWithdraw === "deposit") {
                $totalData = $totalData->where('transactions.isWithdraw', 0);
            } else if ($isWithdraw === "withdraw") {
                $totalData = $totalData->where('transactions.isWithdraw', 1);
            }
        }

        $totalData = $totalData->first();

        if ($totalData) {
            $totalWithdrawApproved = $totalData->totalWithdrawApproved;
            $totalDepositApproved = $totalData->totalDepositApproved;
        } else {
            $totalWithdrawApproved = 0;
            $totalDepositApproved = 0;
        }

        // Query for detailed transaction data
        $playerTransferData = DB::table('transactions')
            ->select(
                'transactions.id as reference_id',
                'users.user_name',
                'transactions.amount',
                'transactions.isWithdraw as type',
                'transactions.created_at'
            )
            ->join('players', 'players.id', '=', 'transactions.player_id')
            ->join('users', 'users.id', '=', 'players.user_id')
            ->where('players.agent_id', $agent_id)
            ->where('transactions.status', 1);

        if ($startDate && $startDate != null) {
            $playerTransferData = $playerTransferData->whereBetween('users.created_at', [$startDate, $endDate]);
        }

        if ($username && $username != null) {
            $playerTransferData = $playerTransferData->where('users.user_name', 'like', '%' . $username . '%');
        }

        if ($isWithdraw && $isWithdraw != null) {
            if ($isWithdraw === "deposit") {
                $playerTransferData = $playerTransferData->where('transactions.isWithdraw', 0);
            } else if ($isWithdraw === "withdraw") {
                $playerTransferData = $playerTransferData->where('transactions.isWithdraw', 1);
            }
        }

        $playerTransferData = $playerTransferData->orderBy('transactions.created_at', 'DESC')->paginate(10);

        return [
            'account' => $playerTransferData,
            'totalWithdrawApproved' => $totalWithdrawApproved,
            'totalDepositApproved' => $totalDepositApproved,
        ];
    }

    public function getPlayerBettingList(Request $request)
    {
        $agent_id = Auth::user()->agent->id;

        // Initialize variables
        $startDate  = null;
        $endDate    = null;
        $username   = null;
        $gameVendor = null;

        // Parse request dates using Carbon
        if ($request && $request->startDate) {
            $startDate = Carbon::parse($request->startDate)->toDateString();
        }
        if ($request && $request->endDate) {
            // Set the end date to the end of the day
            $endDate = Carbon::parse($request->endDate)->endOfDay()->toDateString();
        }
        if ($request && $request->username) {
            $username   =   $request->username;
        }

        if ($request && $request->game_vendor && $request->game_vendor != "all") {
            $gameVendor =   $request->game_vendor;
        }

        $data =  Player::select('bet_rounds.id', 'users.user_name', 'bet_rounds.total_valid_bets', 'bet_rounds.win_loss', 'bet_rounds.created_at','game_platforms.name as game_vendor')
            ->without('wallet', 'user', 'language_name')
            ->where('players.agent_id', $agent_id)
            ->join('bet_rounds', 'bet_rounds.player_id', '=', 'players.id')
            ->join('users', 'users.id', '=', 'players.user_id')
            ->join('game_platforms', 'game_platforms.id', '=', 'bet_rounds.game_platform_id');

        if ($startDate && $startDate != null) {
            $data = $data->whereBetween('bet_rounds.created_at', [$startDate, $endDate]);
        }

        if ($username && $username != null) {
            $data = $data->where('users.user_name', 'like', '%' . $username . '%');
        }

        if ($gameVendor && $gameVendor != null) {
            $data = $data->where('provider_name', 'like', '%' . $gameVendor . '%');
        }

        $data = $data->orderBy('bet_rounds.created_at','DESC')->paginate(15);

        $providers = Player::select('game_platforms.name as game_vendor')
            ->without('wallet', 'user', 'language_name')
            ->where('players.agent_id', $agent_id)
            ->join('bet_rounds', 'bet_rounds.player_id', '=', 'players.id')
            ->join('users', 'users.id', '=', 'players.user_id')
            ->join('game_platforms', 'game_platforms.id', '=', 'bet_rounds.game_platform_id');;

        if ($startDate && $startDate != null) {
            $providers = $providers->whereBetween('bet_rounds.created_at', [$startDate, $endDate]);
        }

        if ($username && $username != null) {
            $providers = $providers->where('users.user_name', 'like', '%' . $username . '%');
        }

        $providers = $providers->distinct()
            ->orderBy('bet_rounds.provider')
            ->pluck('game_vendor');


        return ["data" => $data, "game_vendor" => $providers];
        // ==========================================================================

    }

    public function getRegisteredUsersFirstDepositChart(Request $request)
    {
        $timestampValue =   self::TODAY;
        $agent_id = Auth::user()->agent->id;

        if ($request->timestamp) {
            $timestampValue     =   $request->timestamp;
        }

        $timeData = $this->getChartDataTimeStamp($timestampValue);

        $registeredUsersQuery = DB::table('users')
            ->select(DB::raw('DAY(users.created_at) AS day'), DB::raw('COUNT(*) AS registered_user'))
            ->join('players', 'players.user_id', '=', 'users.id')
            ->where('players.agent_id', $agent_id);

        // Add timestamp conditions
        if ($timestampValue == self::TODAY) {
            $registeredUsersQuery->whereDate('users.created_at', $timeData['today']);
        }
        if ($timestampValue == self::THIS_WEEK || $timestampValue == self::LAST_WEEK) {
            $registeredUsersQuery->whereBetween('users.created_at', [$timeData['weekStart'], $timeData['weekEnd']]);
        }
        if ($timestampValue == self::THIS_MONTH || $timestampValue == self::LAST_MONTH) {
            $registeredUsersQuery->whereBetween('users.created_at', [$timeData['monthStart'], $timeData['monthEnd']]);
        }

        // Group by day and get the count of registered users
        $registeredUsersData = $registeredUsersQuery->groupBy(DB::raw('DAY(users.created_at)'))->get();


        // Query to count all first deposits made by players under the agent on the specified timestamp
        $firstDepositQuery = DB::table('users')
            ->select(DB::raw('DAY(deposits.created_at) AS day'), DB::raw('COUNT(DISTINCT deposits.id) AS first_deposit'))
            ->join('players', 'players.user_id', '=', 'users.id')
            ->join('agents', 'agents.id', '=', 'players.agent_id')
            ->leftJoin('transactions', 'transactions.player_id', '=', 'players.id')
            ->leftJoin('deposits', function ($join) use ($timeData, $timestampValue) {
                $join->on('deposits.transaction_id', '=', 'transactions.id')
                    ->where('deposits.is_first', 1); // Consider only first deposits

                // Add timestamp conditions
                if ($timestampValue == self::TODAY) {
                    $join->whereDate('deposits.created_at', $timeData['today']);
                }
                if ($timestampValue == self::THIS_WEEK || $timestampValue == self::LAST_WEEK) {
                    $join->whereBetween('deposits.created_at', [$timeData['weekStart'], $timeData['weekEnd']]);
                }
                if ($timestampValue == self::THIS_MONTH || $timestampValue == self::LAST_MONTH) {
                    $join->whereBetween('deposits.created_at', [$timeData['monthStart'], $timeData['monthEnd']]);
                }
            })
            ->where('agents.id', $agent_id);

        // Group by day and get the count of first deposits
        $firstDepositData = $firstDepositQuery->groupBy(DB::raw('DAY(deposits.created_at)'))->get();

        if($timestampValue == self::LAST_MONTH)
        {
            $todayDate = Carbon::now()->subMonth();
        }
        else
        {
            $todayDate = Carbon::now();
        }
        
        
        // Get the first and last day of the current month
        $startOfMonth = $todayDate->copy()->startOfMonth();
        $endOfMonth = $todayDate->copy()->endOfMonth();

        // Create an array to store the days
        $daysOfMonth = [];

        // Loop through each day from the first to the last day of the month
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $daysOfMonth[] = $date->day;
        }


        // Merge the data into a single array
        $output = [];
        foreach ($registeredUsersData as $userData) {
            $day = $userData->day;
            $registeredUserCount = $userData->registered_user;
            $firstDepositCount = 0;

            // Search for corresponding first deposit count
            foreach ($firstDepositData as $depositData) {
                if ($depositData->day == $day) {
                    $firstDepositCount = $depositData->first_deposit;
                    break;
                }
            }

            $output[$day] = [
                'registered_user' => (int)$registeredUserCount,
                'first_deposit' => (int)$firstDepositCount
            ];
        }

        return ["list_of_dates" => $daysOfMonth, "transaction_data" => $output];
    }

    public function getWithdrawalDepositChart(Request $request)
    {
        $timestampValue =   self::TODAY;
        $agent_id = Auth::user()->agent->id;
        
        if ($request->timestamp) {
            $timestampValue     =   $request->timestamp;
        }

        $timeData = $this->getChartDataTimeStamp($timestampValue);

        $totalData = DB::table('players')
            ->select(
                DB::raw('DAY(transactions.created_at) AS day'),
                DB::raw('SUM(CASE WHEN transactions.isWithdraw = 1 AND transactions.status = 1 THEN transactions.amount ELSE 0 END) as totalWithdrawApproved'),
                DB::raw('SUM(CASE WHEN transactions.isWithdraw = 0 AND transactions.status = 1 THEN transactions.amount ELSE 0 END) as totalDepositApproved')
            )
            ->join('transactions', 'players.id', '=', 'transactions.player_id')
            ->where('players.agent_id', $agent_id);

        if ($timestampValue == self::TODAY) {
            $totalData = $totalData->whereDate('transactions.created_at', $timeData['today']);
        }
        if ($timestampValue == self::THIS_WEEK || $timestampValue == self::LAST_WEEK) {
            $totalData = $totalData->whereBetween('transactions.created_at', [$timeData['weekStart'], $timeData['weekEnd']]);
        }
        if ($timestampValue == self::THIS_MONTH || $timestampValue == self::LAST_MONTH) {
            $totalData = $totalData->whereBetween('transactions.created_at', [$timeData['monthStart'], $timeData['monthEnd']]);
        }

        $totalData = $totalData
            ->groupBy(DB::raw('DAY(transactions.created_at)'))
            ->havingRaw('SUM(CASE WHEN transactions.isWithdraw = 1 AND transactions.status = 1 THEN transactions.amount ELSE 0 END) > 0 OR SUM(CASE WHEN transactions.isWithdraw = 0 AND transactions.status = 1 THEN transactions.amount ELSE 0 END) > 0')
            ->get();



            if($timestampValue == self::LAST_MONTH)
            {
                $todayDate = Carbon::now()->subMonth();
            }
            else
            {
                $todayDate = Carbon::now();
            }
        
            // Get the first and last day of the current month
            $startOfMonth = $todayDate->copy()->startOfMonth();
            $endOfMonth = $todayDate->copy()->endOfMonth();
    
            // Create an array to store the days
            $daysOfMonth = [];
    
            // Loop through each day from the first to the last day of the month
            for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
                $daysOfMonth[] = $date->day;
            }

        $output = [];
        
        foreach ($totalData as $item) {
            // array_push($daysOfMonth, (string)$item->day);
            $output[$item->day] = [
                'withdrawal'   => (int)$item->totalWithdrawApproved,
                'deposit'      => (int)$item->totalDepositApproved
            ];
        }

        return ["list_of_dates" => $daysOfMonth, "transaction_data" => $output];

    }

    public function getWinLossChart(Request $request)
    {
        $timestampValue = self::TODAY;
        $agent_id = Auth::user()->agent->id;

        if ($request->timestamp) {
            $timestampValue = $request->timestamp;
        }

        $timeData = $this->getChartDataTimeStamp($timestampValue);

        $data = Player::select('bet_rounds.id', 'users.user_name', 'bet_rounds.total_valid_bets', 'bet_rounds.win_loss', 'bet_rounds.provider as game_vendor', 'bet_rounds.created_at')
            ->without('wallet', 'user', 'language_name')
            ->where('players.agent_id', $agent_id)
            ->join('bet_rounds', 'bet_rounds.player_id', '=', 'players.id')
            ->join('users', 'users.id', '=', 'players.user_id');

        if ($timestampValue == self::TODAY) {
            $data = $data->whereDate('bet_rounds.created_at', $timeData['today']);
        }
        if ($timestampValue == self::THIS_WEEK || $timestampValue == self::LAST_WEEK) {
            $data = $data->whereBetween('bet_rounds.created_at', [$timeData['weekStart'], $timeData['weekEnd']]);
        }
        if ($timestampValue == self::THIS_MONTH || $timestampValue == self::LAST_MONTH) {
            $data = $data->whereBetween('bet_rounds.created_at', [$timeData['monthStart'], $timeData['monthEnd']]);
        }

        $results = $data->get();

        if($timestampValue == self::LAST_MONTH)
        {
            $todayDate = Carbon::now()->subMonth();
        }
        else
        {
            $todayDate = Carbon::now();
        }
        
        // Get the first and last day of the current month
        $startOfMonth = $todayDate->copy()->startOfMonth();
        $endOfMonth = $todayDate->copy()->endOfMonth();

        // Create an array to store the days
        $daysOfMonth = [];

        // Loop through each day from the first to the last day of the month
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $daysOfMonth[] = $date->day;
        }

        // $list_of_dates = [];
        $transaction_data = [];

        foreach ($results as $result) {
            $date = date('j', strtotime($result->created_at)); // Extract day from created_at
            // $list_of_dates[] = $date;

            // Check if winLoss is not null before adding it to the array
            if ($result->win_loss !== null) {
                $transaction_data[$date]['winLoss'] = (int) $result->win_loss;
            }
        }

        // Filter out null values from transaction_data
        $transaction_data = array_filter($transaction_data);

        return ["list_of_dates" => array_values(array_unique($daysOfMonth)), "transaction_data" => $transaction_data];
    }

    public function getAllStatsChart(Request $request)
    {
        $agent_id = Auth::user()->agent->id;

        $dates = [
            'today' => now()->toDateString(),
            'yesterday' => now()->subDay()->toDateString(),
            'this_week' => [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ],
            'last_week' => [
                now()->startOfWeek()->subWeek()->toDateString(),
                now()->endOfWeek()->subWeek()->toDateString(),
            ],
            'this_month' => [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ],
        ];

        // Initialize arrays to store counts for each date range
        $logins             = [];
        $registers          = [];
        $firstDeposits      = [];
        $depositAmount      = [];
        $depositCount       = [];
        $withdrawalAmount   = [];
        $withdrawalCount    = [];
        $validBetting       = [];
        $winLossBetting     = [];
        $totalBettings      = [];

        foreach ($dates as $key => $value) {
            // Query to fetch login counts
            $query = Player::select('id')
                ->where('agent_id', $agent_id)
                ->join('login_histories', 'login_histories.user_id', '=', 'players.user_id');

            if (is_array($value)) {
                $query->whereBetween('login_histories.created_at', $value);
            } else {
                $query->whereDate('login_histories.created_at', $value);
            }

            // Query to fetch registered users
            $registered = Player::select('id')
                ->with('user')
                ->where('agent_id', $agent_id)
                ->whereHas('user', function ($query) use ($key, $value) {
                    if (is_array($value)) {
                        $query->whereBetween('created_at', $value);
                    } else {
                        $query->whereDate('created_at', $value);
                    }
                });

            // Query to fetch first deposits
            $firstDeposit = DB::table('users')
                            ->select('users.id')
                            ->leftJoin('players', 'players.user_id', '=', 'users.id')
                            ->leftJoin('transactions', 'transactions.player_id', '=', 'players.id')
                            ->leftJoin('deposits', 'deposits.transaction_id', '=', 'transactions.id')
                            ->where('players.agent_id', $agent_id)
                            ->where('deposits.is_first', 1);

            if (is_array($value)) {
                $firstDeposit->whereBetween('deposits.created_at', $value);
            } else {
                $firstDeposit->whereDate('deposits.created_at', $value);
            }

            // Query to fetch betting data
            $bettingQuery = BetRound::whereHas('player', function ($query) use ($agent_id) {
                $query->where('agent_id', $agent_id);
            });

            if (is_array($value)) {
                $bettingQuery->whereBetween('created_at', $value);
            } else {
                $bettingQuery->whereDate('created_at', $value);
            }

            $totalValidBetsSum = $bettingQuery->sum('total_valid_bets');
            $winLossSum = $bettingQuery->sum('win_loss');

            // Query to fetch deposit data
            $depositData = DB::table('players')
                ->select(DB::raw('SUM(CASE WHEN transactions.isWithdraw = 0 AND transactions.status = 1 THEN transactions.amount ELSE 0 END) as totalDepositApproved'))
                ->join('transactions', 'players.id', '=', 'transactions.player_id')
                ->where('transactions.isWithdraw', 0)
                ->where('players.agent_id', $agent_id)
                ->groupBy('players.id');

            if (is_array($value)) {
                $depositData->whereBetween('transactions.created_at', $value);
            } else {
                $depositData->whereDate('transactions.created_at', $value);
            }

            // Query to fetch withdrawal data
            $withdrawalData = DB::table('players')
                ->select(DB::raw('SUM(CASE WHEN transactions.isWithdraw = 1 AND transactions.status = 1 THEN transactions.amount ELSE 0 END) as totalWithdrawApproved'))
                ->join('transactions', 'players.id', '=', 'transactions.player_id')
                ->where('transactions.isWithdraw', 1)
                ->where('players.agent_id', $agent_id)
                ->groupBy('players.id');

            if (is_array($value)) {
                $withdrawalData->whereBetween('transactions.created_at', $value);
            } else {
                $withdrawalData->whereDate('transactions.created_at', $value);
            }

            // Fetch and store the results
            $logins[$key]               = $query->count();
            $registers[$key]            = $registered->count();
            $firstDeposits[$key]        = $firstDeposit->count();
            $depositCount[$key]         = $depositData->count();
            $depositData                = $depositData->first();
            $depositAmount[$key]        = $depositData    ? (int)$depositData->totalDepositApproved : 0;
            $withdrawalCount[$key]      = $withdrawalData->count();
            $withdrawalData             = $withdrawalData->first();
            $withdrawalAmount[$key]     = $withdrawalData ? (int)$withdrawalData->totalWithdrawApproved : 0;
            $totalBettings[$key]        = $bettingQuery->count();
            $validBetting[$key]         = (int) $totalValidBetsSum;
            $winLossBetting[$key]       = (int) $winLossSum;
        }

        return [
            'logins'                => $logins,
            'registered'            => $registers,
            'firstDeposit'          => $firstDeposits,
            'depositAmount'         => $depositAmount,
            'depositCount'          => $depositCount,
            'withdrawalAmount'      => $withdrawalAmount,
            'withdrawalCount'       => $withdrawalCount,
            'validBetting'          => $validBetting,
            'winLossBetting'        => $winLossBetting,
            'totalBettings'         => $totalBettings,
        ];
    }
    public function getChartDataTimeStamp($timestampValue)
    {
        $today          =   null;
        $weekStart      =   null;
        $weekEnd        =   null;
        $monthStart     =   null;
        $monthEnd       =   null;

        if ($timestampValue == self::TODAY) {
            $today  =   Carbon::now()->toDateString();
        } else if ($timestampValue == self::THIS_WEEK) {
            $weekStart  =   Carbon::now()->startOfWeek()->toDateString();
            $weekEnd    =   Carbon::now()->endOfMonth()->toDateString();
        } else if ($timestampValue == self::THIS_MONTH) {
            $monthStart =   Carbon::now()->startOfMonth()->toDateString();
            $monthEnd   =   Carbon::now()->endOfMonth()->toDateString();
        }
        if ($timestampValue == self::LAST_WEEK) {
            $weekStart  =   Carbon::now()->subWeek()->startOfWeek()->toDateString();
            $weekEnd    =   Carbon::now()->subWeek()->endOfWeek()->toDateString();
        }
        if ($timestampValue == self::LAST_MONTH) {
            $monthStart =   Carbon::now()->subMonth()->startOfMonth()->toDateString();
            $monthEnd   =   Carbon::now()->subMonth()->endOfMonth()->toDateString();
        }

        return [
            "today" => $today,
            "weekStart" => $weekStart,
            "weekEnd" => $weekEnd,
            "monthStart" => $monthStart,
            "monthEnd" => $monthEnd
        ];
    }

    public function getStatsData(Request $request)
    {
        $timestampValue = self::TODAY;

        if ($request->timestamp) {
            $timestampValue = $request->timestamp;
        }

        $agent_id = Auth::user()->agent->id;
        $timestampData = $this->getChartDataTimeStamp($timestampValue);

        $totalPlayers = DB::table('players')->where('agent_id', $agent_id)->count();

        $totalSignUpsQuery = DB::table('players')
            ->join('users', 'users.id', '=', 'players.user_id')
            ->where('agent_id', $agent_id);

        if ($timestampData["today"]) {
            $totalSignUpsQuery->whereDate('users.created_at', '=', $timestampData["today"]);
        } elseif ($timestampData["weekStart"] && $timestampData["weekEnd"]) {
            $totalSignUpsQuery->whereBetween('users.created_at', [$timestampData["weekStart"], $timestampData["weekEnd"]]);
        } elseif ($timestampData["monthStart"] && $timestampData["monthEnd"]) {
            $totalSignUpsQuery->whereBetween('users.created_at', [$timestampData["monthStart"], $timestampData["monthEnd"]]);
        }

        $totalSignUps = $totalSignUpsQuery->count();

        $firstDepositQuery = DB::table('transactions')
            ->select(DB::raw('SUM(CASE WHEN deposits.is_first = 1 AND transactions.status = 1 THEN transactions.amount ELSE 0 END) as totalFirstDeposit'))
            ->join('deposits', 'transactions.id', '=', 'deposits.transaction_id')
            ->join('players', 'players.id', '=', 'transactions.player_id')
            ->where('players.agent_id', $agent_id);

        if ($timestampData["today"]) {
            $firstDepositQuery->whereDate('transactions.created_at', '=', $timestampData["today"]);
        } elseif ($timestampData["weekStart"] && $timestampData["weekEnd"]) {
            $firstDepositQuery->whereBetween('transactions.created_at', [$timestampData["weekStart"], $timestampData["weekEnd"]]);
        } elseif ($timestampData["monthStart"] && $timestampData["monthEnd"]) {
            $firstDepositQuery->whereBetween('transactions.created_at', [$timestampData["monthStart"], $timestampData["monthEnd"]]);
        }

        $firstDeposit = $firstDepositQuery->first();

        $depositData = DB::table('players')
                ->select(DB::raw('SUM(CASE WHEN transactions.isWithdraw = 0 AND transactions.status = 1 THEN transactions.amount ELSE 0 END) as totalDepositApproved'))
                ->join('transactions', 'players.id', '=', 'transactions.player_id')
                ->where('transactions.isWithdraw', 0)
                ->where('players.agent_id', $agent_id)
                ->groupBy('players.id');

                if ($timestampData["today"]) {
                    $depositData->whereDate('transactions.created_at', '=', $timestampData["today"]);
                } elseif ($timestampData["weekStart"] && $timestampData["weekEnd"]) {
                    $depositData->whereBetween('transactions.created_at', [$timestampData["weekStart"], $timestampData["weekEnd"]]);
                } elseif ($timestampData["monthStart"] && $timestampData["monthEnd"]) {
                    $depositData->whereBetween('transactions.created_at', [$timestampData["monthStart"], $timestampData["monthEnd"]]);
                }

            // Query to fetch withdrawal data
        $withdrawalData = DB::table('players')
                ->select(DB::raw('SUM(CASE WHEN transactions.isWithdraw = 1 AND transactions.status = 1 THEN transactions.amount ELSE 0 END) as totalWithdrawApproved'))
                ->join('transactions', 'players.id', '=', 'transactions.player_id')
                ->where('transactions.isWithdraw', 1)
                ->where('players.agent_id', $agent_id)
                ->groupBy('players.id');

                if ($timestampData["today"]) {
                    $withdrawalData->whereDate('transactions.created_at', '=', $timestampData["today"]);
                } elseif ($timestampData["weekStart"] && $timestampData["weekEnd"]) {
                    $withdrawalData->whereBetween('transactions.created_at', [$timestampData["weekStart"], $timestampData["weekEnd"]]);
                } elseif ($timestampData["monthStart"] && $timestampData["monthEnd"]) {
                    $withdrawalData->whereBetween('transactions.created_at', [$timestampData["monthStart"], $timestampData["monthEnd"]]);
                }

        $totalWithdrawApproved = $withdrawalData->first();
        $totalDepositApproved = $depositData->first();

        $totalWithdrawApproved = $totalWithdrawApproved ? (int)$totalWithdrawApproved->totalWithdrawApproved : 0;
        $totalDepositApproved = $totalDepositApproved ? (int)$totalDepositApproved->totalDepositApproved : 0;
        return [
            "totalDeposit" => $totalDepositApproved,
            "totalWithdraw" => $totalWithdrawApproved,
            "totalPlayers" => (int)$totalPlayers ? $totalPlayers : 0,
            "totalSignUps" => (int)$totalSignUps ? $totalSignUps : 0,
            "totalFirstDeposits" => (int)$firstDeposit->totalFirstDeposit ?? 0
        ];
    }

}
