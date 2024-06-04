<?php

namespace App\Http\Controllers;

use App\Constants\BetRoundConstants;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Admin;
use App\Models\Player;
use App\Models\AdminLog;
use App\Models\GameItem;
use Illuminate\Http\Request;
use App\Constants\UserConstants;
use App\Models\GameAccessHistory;
use App\Models\UserPaymentMethod;
use App\Constants\GlobalConstants;
use App\Models\AgentChangeHistory;
use App\Constants\GameItemConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Http\Requests\ViewPlayerRequest;
use App\Http\Requests\ListPlayersRequest;
use App\Http\Requests\LoginPlayerRequest;
use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerRequest;
use App\Http\Requests\RegisterPlayerRequest;
use App\Services\LogService\AdminLogService;
use App\Http\Requests\CheckPlayerNameRequest;
use App\Http\Requests\PlayerGameLoginRequest;
use App\Http\Requests\ViewPlayerBanksRequest;
use App\Http\Requests\CheckPlayerPhoneRequest;
use App\Services\WebService\WebRequestService;
use App\Http\Requests\PlayerChangeAgentRequest;
use App\Http\Requests\PlayerLatestLoginRequest;
use App\Services\GameService\GlobalGameService;
use App\Http\Requests\PlayerToggleStatusRequest;
use App\Http\Requests\PlayerAdjustBalanceRequest;
use App\Http\Requests\PlayerToggleBettingRequest;
use App\Http\Requests\PlayerToggleWithdrawRequest;
use App\Models\Agent;
use App\Models\BetRound;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Withdraw;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{

    public function create(RegisterPlayerRequest $request)
    {
        DB::beginTransaction();

        try {

            $user = User::create($request->getUserData());
            $playerData = $request->getPlayerData();

            if ($request->has('agent_id')) {

                if ($request->input('agent_id')) {
                    $agent = Agent::where('unique_code', $request->input('agent_id'))->first();

                    if (!$agent) {

                        DB::rollBack();
                        return response()->json([
                            'status' => false,
                            'message' => 'AGENT_NOT_FOUND'
                        ], 404);
                    }

                    // Add agent_id to player data
                    $playerData['agent_id'] = $agent->id;
                }
            }

            // Create the player associated with the user
            $player = $user->player()->create($playerData);

            // Create the wallet associated with the player
            $player->wallet()->create($request->getWalletData());

            $webRequestService = new WebRequestService($request);
            $user->loginHistory()->create(['ip' => $webRequestService->getIpAddress()]);

            // Commit the transaction
            DB::commit();

            // Return success response with the token
            return response()->json([
                'user_id'=>$user->id,
                'status' => true,
                'message' => 'PLAYER_CREATED_SUCCESSFULLY',
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'PLAYER_CREATION_FAILED',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkName(CheckPlayerNameRequest $request)
    {
        $validated = $request->validated();

        $exists = User::firstWhere('user_name', $validated['user_name']) ? true : false;

        return response()->json([
            'status' => true,
            'exists' => $exists,
        ]);
    }

    public function checkPhone(CheckPlayerPhoneRequest $request)
    {
        $validated = $request->validated();

        $exists = User::firstWhere('phone', $validated['phone']) ? true : false;

        return response()->json([
            'status' => true,
            'exists' => $exists,
        ]);
    }

    public function getLanguages()
    {
        return GlobalConstants::getLanguages();
    }

    public function getCurrencies()
    {
        return GlobalConstants::getCurrencies();
    }

    public function login(LoginPlayerRequest $request)
    {
        $validated = $request->validated();

        if (!$user = User::checkPlayerUserName($validated['user_name'])) {
            return response()->json([
                'status' => false,
                'message' => 'USER_DOES_NOT_EXIST'
            ], 404);
        }

        if (!$user->player->active) {
            return response()->json([
                'status' => false,
                'message' => 'ACCOUNT_INACTIVE'
            ], 402);
        }

        if (!$user->verifyPassword($validated['password'])) {

            $this->incrementLoginAttempts($user->id);

            // Check if login attempts exceeded
            if ($this->isLoginAttemptsExceeded($user->id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'LOGIN_ATTEMPTS_EXCEEDED'
                ], 403);
            }


            return response()->json([
                'status' => false,
                'message' => 'PASSWORD_INCORRECT'
            ], 403);
        }

        $this->resetLoginAttempts($user->id);

        $user->tokens()->delete();

        $webrequestservice = new WebRequestService($request);

        $request_ip = $webrequestservice->getIpAddress();

        $user->loginHistory()->create(['ip' => $request_ip]);

        $user_details = [
            "user_id" => $user->player->user->id,
            "user_name" => $user->player->user->user_name,
            'payer_id' => $user->player->id,
        ];

        return response()->json([
            'status' => true,
            'message' => 'USER_LOGGED_IN_SUCCESSFULLY',
            'token' => $user->createToken("API TOKEN")->plainTextToken,
            'data' => $user_details
        ], 200);
    }

    private function incrementLoginAttempts($userId)
    {
        $cacheKey = 'login_attempts_' . $userId;
        $loginAttempts = cache($cacheKey, 0);

        cache([$cacheKey => $loginAttempts + 1], now()->addHours(1)); // Increase attempts and set expiration time
        // cache([$cacheKey => $loginAttempts + 1], now()->addMinutes(2)); // Increase attempts and set expiration time

    }

    private function isLoginAttemptsExceeded($userId)
    {
        $cacheKey = 'login_attempts_' . $userId;
        $loginAttempts = cache($cacheKey, 0);
        $maxAttempts = 10; // Maximum login attempts allowed

        return $loginAttempts >= $maxAttempts;
    }

    private function resetLoginAttempts($userId)
    {
        $cacheKey = 'login_attempts_' . $userId;
        cache([$cacheKey => 0], now()->addHours(1)); // Reset attempts and set expiration time
        // cache([$cacheKey => 0], now()->addMinutes(2)); // Reset attempts and set expiration time
    }


    public function profile()
    {
        return response()->json(Auth::user()->player->profile());
    }

    public function listPlayers(ListPlayersRequest $request)
    {
        return Player::listPlayers($request->validated(), Auth::user()->can('View Player Phone'))->orderByDesc('id')->paginate(10, ['id', 'user_id', 'agent_id', 'active']);
    }

    public function listLatestPlayers()
    {
        return Player::with(['user:id,name,user_name,phone'])
            ->orderByDesc('id')
            ->take(10)
            ->get(['id', 'user_id', 'agent_id', 'active', 'language']);
    }

    public function store(StorePlayerRequest $request)
    {
        $user = User::create($request->getUserData());

        $user->uploadProfilePicture($request->file('profile_pic'))->save();

        $player = $user->player()->create($request->getPlayerData());

        $player->wallet()->create($request->getWalletData());

        return response()->json([
            'status' => true,
            'message' => 'PLAYER_CREATED_SUCCESSFULLY',
        ], 200);
    }

    public function update(UpdatePlayerRequest $request, Player $player)
    {
        if ($player->update($request->getPlayerData()) && $player->user->update($request->getUserData()) && $player->wallet->update($request->getWalletData())) {

            return response()->json([
                'status' => true,
                'message' => 'PLAYER_UPDATED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PLAYER_UPDATE_FAILED',
        ], 400);
    }

    public function view(ViewPlayerRequest $request, Player $player)
    {
        return Player::view()->find($player->id);
    }

    public function latestLogin(PlayerLatestLoginRequest $request, Player $player)
    {
        return Player::latestLogin()->find($player->id, ['id', 'user_id']);
    }

    public function toggleStatus(PlayerToggleStatusRequest $request, Player $player)
    {
        $playerStatus = $player->active;
        if ($player->update(['active' => !$player->active])) {
            $webrequestservice = new WebRequestService($request);
            AdminLogService::createLog('Player ' . $player->user->user_name . ' status changed from ' . ($playerStatus ? "on" : "off") . ' to ' . ($player->active ? "on" : "off"));
            return response()->json([
                'status' => true,
                'active' => $player->active,
                'message' => 'STATUS_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'STATUS_UPDATE_FAILED'
        ], 400);
    }

    public function toggleWithdraw(PlayerToggleWithdrawRequest $request, Player $player)
    {
        $allowWithdraw = $player->allow_withdraw;
        if ($player->update(['allow_withdraw' => !$player->allow_withdraw])) {
            $webrequestservice = new WebRequestService($request);
            AdminLogService::createLog('Player ' . $player->user->user_name . ' withdraw status changed from ' . ($allowWithdraw ? "on" : "off") . ' to ' . ($player->allow_withdraw ? "on" : "off"));
            return response()->json([
                'status' => true,
                'allowed' => $player->allow_withdraw,
                'message' => 'WITHDRAW_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'WITHDRAW_UPDATE_FAILED'
        ], 400);
    }

    public function toggleBetting(PlayerToggleBettingRequest $request, Player $player)
    {
        $allowBetting = $player->allow_betting;
        if ($player->update(['allow_betting' => !$player->allow_betting])) {
            $webrequestservice = new WebRequestService($request);
            AdminLogService::createLog('Player ' . $player->user->user_name . ' betting status changed from ' . ($allowBetting ? "on" : "off") . ' to ' . ($player->allow_betting ? "on" : "off"));
            return response()->json([
                'status' => true,
                'allowed' => $player->allow_betting,
                'message' => 'BETTING_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'BETTING_UPDATED_FAILED'
        ], 400);
    }

    public function toggleDeposit(PlayerToggleBettingRequest $request, Player $player)
    {
        if ($player->update(['allow_deposit' => !$player->allow_deposit])) {

            return response()->json([
                'status' => true,
                'allowed' => $player->allow_deposit,
                'message' => 'DEPOSIT_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'DEPOSIT_UPDATED_FAILED'
        ], 400);
    }

    public function changeAgent(PlayerChangeAgentRequest $request, Player $player)
    {
        $validated = $request->validated();

        $agent_change_history_data = $request->getAgentChangeHistoryData();

        if ($player->update(['agent_id' => $validated['agent_id']])) {

            AgentChangeHistory::create($agent_change_history_data);

            return response()->json([
                'status' => true,
                'message' => 'PLAYER_AGENT_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PLAYER_AGENT_UPDATE_FAILED'
        ], 400);
    }

    public function getBalance()
    {
        return Auth::user()->player->wallet->balance;
    }

    public function adjustBalance(PlayerAdjustBalanceRequest $request, Player $player)
    {
        $validated = $request->validated();

        $player_balance_history_data = $request->getPlayerBalanceHistoryData();
        $previousBalance = $player->wallet->balance;
        if ($player->wallet->adjustBalance($validated['amount'])) {
            $webrequestservice = new WebRequestService($request);
            AdminLogService::createLog('Player ' . $player->user->user_name . ' balance changed from ' . $previousBalance . ' to ' . $validated['amount']);
            $player->playerBalanceHistories()->create($player_balance_history_data);

            return response()->json([
                'status' => true,
                'message' => 'ADJUSTMENT_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'ADJUSTMENT_FAILED'
        ], 400);
    }

    public function checkIfAllowWithdraw()
    {
        $player = Auth::user()->player;

        if ($player->allow_withdraw) {

            return response()->json([
                'status' => true,
                'message' => 'PLAYER_ALLOWED_TO_WITHDRAW'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PLAYER_NOT_ALLOWED_TO_WITHDRAW'
        ], 200);
    }

    public function checkIfAllowDeposit()
    {
        $player = Auth::user()->player;

        if ($player->allow_deposit) {

            return response()->json([
                'status' => true,
                'message' => 'PLAYER_ALLOWED_TO_DEPOSIT'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PLAYER_NOT_ALLOWED_TO_DEPOSIT'
        ], 200);
    }

    public function gameLogin(PlayerGameLoginRequest $request)
    {
        try {

            $player = Auth::user()->player;

            $validated = $request->validated();

            if (!$player->allow_betting) {

                return response()->json([
                    'status' => false,
                    'message' => 'ADMIN_FORBIDDEN'
                ], 403);
            }

            $game_item = GameItem::find($validated['game_id']);

            if ($game_item->status != GameItemConstants::STATUS_ACTIVE) {

                return response()->json([
                    'status' => false,
                    'message' => 'GAME_NOT_ACTIVE'
                ], 403);
            }

            if (!$game_item->isCurrency($player->wallet->currency)) {

                return response()->json([
                    'status' => false,
                    'message' => 'CURRENCY_NOT_SUPPORTED'
                ], 403);
            }

            $platform = $game_item->gamePlatform->platform_code;

            Log::info("Game Login Platform: $platform");
            Log::info("Game Login Game Name: $game_item->name");

            $game_service = new GlobalGameService($platform, $game_item->game_id);

            $provider = $game_service->getProvider($player);

            if (isset($provider)) {

                $is_debug = !(Config::get('app.env') === 'production');

                $game_access_history = GameAccessHistory::gameLogin($player, $game_item);

                $deviceType = getDeviceType($request->header('User-Agent'));

                $webrequestservice = new WebRequestService($request);

                $request_ip = $webrequestservice->getIpAddress();

                $result_debug = $provider->loginToGame(null, $request_ip, $deviceType);

                $result = manageGameLoginResult($result_debug, $platform);

                if ($result === 'REGISTER') {
                    Log::info('Registering to Game. player controlelr');

                    $register_debug = $provider->registerToGame($player->language, $request_ip);

                    $register = manageRegisterToGameResult($register_debug, $platform);

                    if ($result == 'CURRENCY_NOT_SUPPORTED') {

                        $game_access_history->gameActionFailed('Game Currency Not Supported');

                        return response()->json([
                            'status' => false,
                            'message' => 'CURRENCY_NOT_SUPPORTED',
                            'result_debug' => $is_debug ? json_decode($result_debug) : null,
                        ], 403);
                    }

                    if ($register !== 'SUCCESS') {

                        $game_access_history->gameActionFailed('Game Registration Network Error');

                        return response()->json([
                            'status' => false,
                            'message' => 'REGISTRATION_NETWORK_ERROR',
                            'register_debug' => $is_debug ? json_decode($register_debug) ?? $register_debug : null
                        ], 403);
                    }

                    $result_debug = $provider->loginToGame(null, $request_ip, $deviceType);

                    $result = manageGameLoginResult($result_debug, $platform);
                }

                if ($result == 'CURRENCY_NOT_SUPPORTED') {

                    $game_access_history->gameActionFailed('Game Currency Not Supported');

                    return response()->json([
                        'status' => false,
                        'message' => 'CURRENCY_NOT_SUPPORTED',
                        'result_debug' => $is_debug ? json_decode($result_debug) : null,
                    ], 403);
                }

                if (is_null($result)) {

                    $game_access_history->gameActionFailed('Getting Game Link Network Error');

                    return response()->json([
                        'status' => false,
                        'message' => 'LOGIN_NETWORK_ERROR',
                        'result_debug' => $is_debug ? json_decode($result_debug) : null,
                    ], 403);
                }

                $game_access_history->gameActionSuccess();

                return response()->json([
                    'status' => true,
                    'message' => 'SUCCESS',
                    'result_debug' => $is_debug ? json_decode($result_debug) : null,
                    'result' => $result,
                ], 200);
            }

            $game_access_history = GameAccessHistory::gameLogin($player, $game_item);

            $game_access_history->gameActionFailed('Game Provider Not Supported');

            return response()->json([
                'status' => false,
                'message' => 'GAME_PROVIDER_NOT_SUPPORTED'
            ], 501);
        } catch (\Exception $exception) {
            Log::info('######################################################################################');
            Log::info('Game Login Exception');
            Log::info($exception);
            Log::info('######################################################################################');


            return response()->json([
                'status' => false,
                'message' => 'UNEXPECTED_ERROR'
            ], 500);
        }
    }

    public function getGender()
    {
        return UserConstants::getGenders();
    }

    public function getPlayerStatistics($playerId)
    {

        $totalApprovedDepositCount = Transaction::where('player_id', $playerId)
            ->where('isWithdraw', false)
            ->where('status', 1)
            ->count();

        // Get total approved deposit transaction sum
        $totalApprovedDepositSum = Transaction::where('player_id', $playerId)
            ->where('isWithdraw', false)
            ->where('status', 1)
            ->sum('amount');

        // Get average deposit amount
        $averageDepositAmount = Transaction::where('player_id', $playerId)
            ->where('isWithdraw', false)
            ->where('status', 1)
            ->avg('amount');

        // Get total approved withdraw transaction count
        $totalApprovedWithdrawCount = Transaction::where('player_id', $playerId)
            ->where('isWithdraw', true)
            ->where('status', 1)
            ->count();

        // Get total approved withdraw transaction sum
        $totalApprovedWithdrawSum = Transaction::where('player_id', $playerId)
            ->where('isWithdraw', true)
            ->where('status', 1)
            ->sum('amount');

        // Get average withdraw amount
        $averageWithdrawAmount = Transaction::where('player_id', $playerId)
            ->where('isWithdraw', true)
            ->where('status', 1)
            ->avg('amount');

        // Get first deposit date time
        $firstDepositDateTime = Transaction::where('player_id', $playerId)
            ->where('isWithdraw', false)
            ->where('status', 1)
            ->orderBy('created_at', 'asc')
            ->value('created_at');

        // Get last deposit date time
        $lastDepositDateTime = Transaction::where('player_id', $playerId)
            ->where('isWithdraw', false)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->value('created_at');

        // Get first withdrawal date time
        $firstWithdrawalDateTime = Transaction::where('player_id', $playerId)
            ->where('isWithdraw', true)
            ->where('status', 1)
            ->orderBy('created_at', 'asc')
            ->value('created_at');

        // Get last withdrawal date time
        $lastWithdrawalDateTime = Transaction::where('player_id', $playerId)
            ->where('isWithdraw', true)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->value('created_at');

        $total_turnover = BetRound::where('player_id', $playerId)
            ->statusIn([BetRoundConstants::STATUS_CLOSED, BetRoundConstants::STATUS_RECLOSED])
            ->sum('total_turnovers');

        $total_valid_bets = BetRound::where('player_id', $playerId)
            ->statusIn([BetRoundConstants::STATUS_CLOSED, BetRoundConstants::STATUS_RECLOSED])
            ->sum('total_valid_bets');

        $total_win_loss = BetRound::where('player_id', $playerId)
            ->statusIn([BetRoundConstants::STATUS_CLOSED, BetRoundConstants::STATUS_RECLOSED])
            ->sum('win_loss');

        // Output the results
        return response()->json([
            'total_approved_deposit_count' => $totalApprovedDepositCount,
            'total_approved_deposit_sum' => $totalApprovedDepositSum,
            'average_deposit_amount' => $averageDepositAmount,
            'total_approved_withdraw_count' => $totalApprovedWithdrawCount,
            'total_approved_withdraw_sum' => $totalApprovedWithdrawSum,
            'average_withdraw_amount' => $averageWithdrawAmount,
            'first_deposit_date_time' => $firstDepositDateTime,
            'last_deposit_date_time' => $lastDepositDateTime,
            'first_withdrawal_date_time' => $firstWithdrawalDateTime,
            'last_withdrawal_date_time' => $lastWithdrawalDateTime,
            'total_turnover' => $total_turnover,
            'total_valid_bets' => $total_valid_bets,
            'total_win_loss' => $total_win_loss,
        ]);
    }
}
