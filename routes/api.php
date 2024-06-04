<?php

use App\Models\User;
use App\Models\AdminLog;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Contracts\Role;
use App\Http\Controllers\KMController;
use App\Http\Controllers\SSController;
use App\Http\Controllers\UGController;
use App\Http\Controllers\AWCController;
use App\Http\Controllers\CMDController;
use App\Http\Controllers\EVOController;
use App\Http\Controllers\ONEController;
use App\Http\Controllers\VIAController;
use App\Http\Controllers\DS88Controller;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SABAController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\AdminLogController;
use App\Http\Controllers\BankCodeController;
use App\Http\Controllers\BetRoundController;
use App\Http\Controllers\GameItemController;
use App\Http\Controllers\PinnacleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\WithdrawController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WhitelistIPController;
use App\Services\PaymentService\PaymentService;
use App\Http\Controllers\GameCategoryController;
use App\Http\Controllers\GamePlatformController;
use App\Http\Controllers\LoginHistoryController;
use App\Http\Controllers\PlayerRatingController;
use App\Http\Controllers\PaymentMethodController;
use App\Services\Providers\UGProvider\UGProvider;
use App\Http\Controllers\PaymentCategoryController;
use App\Http\Controllers\PlayerSiteMediaController;
use App\Http\Controllers\WinLossPurchaseController;
use App\Services\PaymentService\PaymentServiceEnum;
use App\Http\Controllers\GameAccessHistoryController;
use App\Http\Controllers\PromotionCategoryController;
use App\Http\Controllers\UserPaymentMethodController;
use App\Http\Controllers\AgentChangeHistoryController;
use App\Http\Controllers\PermissionCategoryController;
use App\Http\Controllers\PlayerNotificationController;
use App\Http\Controllers\PaymentMethodHistoryController;
use App\Http\Controllers\PlayerBalanceHistoryController;
use App\Http\Controllers\GameTransactionHistoryController;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\LevelController;
use App\Services\Providers\SSProvider\Enums\SSActionsEnums;
use App\Services\Providers\UGProvider\Enums\UGActionsEnums;
use App\Services\Providers\SSProvider\Enums\SSCurrencyEnums;
use App\Services\Providers\UGProvider\Enums\UGCurrencyEnums;
use App\Services\Providers\CMDProvider\Enums\CMDActionsEnums;
use App\Services\Providers\EVOProvider\Enums\EVOActionsEnums;
use App\Services\Providers\ONEProvider\Enums\ONEActionsEnums;
use App\Services\Providers\AWCProvider\Enums\AWCCurrencyEnums;
use App\Services\Providers\CMDProvider\Enums\CMDCurrencyEnums;
use App\Services\Providers\EVOProvider\Enums\EVOCurrencyEnums;
use App\Services\Providers\ONEProvider\Enums\ONECurrencyEnums;
use App\Services\Providers\VIAProvider\Enums\VIACurrencyEnums;
use App\Services\Providers\DS88Provider\Enums\DS88ActionsEnums;
use App\Services\Providers\DS88Provider\Enums\DS88CurrencyEnums;
use App\Services\Providers\SABAProvider\Enums\SABACurrencyEnums;
use App\Services\Providers\PinnacleProvider\Enums\PinnacleActionsEnums;
use App\Services\Providers\PinnacleProvider\Enums\PinnacleCurrencyEnums;
use App\Http\Controllers\TransferWalletController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OTPController;
use App\Http\Controllers\TrendingCategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('user')->group(function () {
    Route::get('/phone/codes', [UserController::class, 'getPhoneCodes']);
    Route::get('/bank/codes', [BankCodeController::class, 'dropDownUser']);
    Route::get('/payment/categories', [PaymentCategoryController::class, 'dropDown']);
    Route::get('/countries', [UserController::class, 'listCountries']);
    Route::get('/payment/code/enum', function () {
        return PaymentServiceEnum::toArray();
    });
});

Route::prefix('user')->middleware(['auth:sanctum'])->group(function () {
    Route::put('/{user}/password/reset', [UserController::class, 'passwordReset']);
    Route::post('/{user}/role/assign', [UserController::class, 'assignRole']);
});
    
Route::prefix('player')->group(function () {
    Route::post('/create', [PlayerController::class, 'create'])->name('player_register_api');
    Route::post('/check/name', [PlayerController::class, 'checkName']);
    Route::post('/check/phone', [PlayerController::class, 'checkPhone']);
    Route::get('/languages', [PlayerController::class, 'getLanguages']);
    Route::get('/currencies', [PlayerController::class, 'getCurrencies']);
    Route::post('/login', [PlayerController::class, 'login'])->name('player_login_api');
    Route::get('/get/genders', [PlayerController::class, 'getGender']);
    Route::get('/game_categories', [GameCategoryController::class, 'getCategories']);
    Route::get('/game_category_details', [GameCategoryController::class, 'listGameCategories']);
    // Route::get('{game_category_id}/game_items', [GameItemsController::class, 'listGameItems']);
    // Route::get('promotions', [PromotionController::class, 'listPromotionsForPlayers']);
    Route::get('/promotions', [PromotionCategoryController::class, 'listPromotionCategories']);
    

});

Route::post('/banners/homepage/set', [PlayerSiteMediaController::class, 'setHomePageBanner']);
Route::get('/providers/getbycategory/{gameCategory}', [GamePlatformController::class, 'getPlatformsForCategory']);

// betting_providers list
Route::get('/providers/list', [GamePlatformController::class, 'getProviders']);
Route::get('/getAlltrendingCategory', [TrendingCategoryController::class, 'getAlltrendingCategory']);


Route::prefix('player')->middleware(['auth:sanctum', 'scope.player'])->group(function () {
    Route::get('/profile', [PlayerController::class, 'profile']);
    Route::get('/transactions', [TransactionController::class, 'listPlayerTransactions']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'playerView']);
    Route::get('/balance', [PlayerController::class, 'getBalance']);
    Route::post('/game/login', [PlayerController::class, 'gameLogin']);
    Route::get('/withdraw/payment/methods', [PaymentMethodController::class, 'getDepositBanks']);
    Route::get('/payment/methods', [PaymentCategoryController::class, 'listDepositPaymentCategories']);
    Route::post('/deposit', [DepositController::class, 'deposit']);
    Route::post('/withdraw', [WithdrawController::class, 'withdraw'])->name('player_withdraw_api');
    Route::get('/check/allow/withdraw', [PlayerController::class, 'checkIfAllowWithdraw']);
    Route::get('/check/allow/deposit', [PlayerController::class, 'checkIfAllowDeposit']);
    Route::post('/user/payment/method/create', [UserPaymentMethodController::class, 'create']);
    Route::put('/user/payment/method/update', [UserPaymentMethodController::class, 'update']);
    Route::get('/user/payment/method/list', [UserPaymentMethodController::class, 'listUserPaymentMethods']);
    Route::get('/user/payment/method/{user_payment_method}', [UserPaymentMethodController::class, 'delete']);
    // Route::get('bank/codes/{payment_category_id}', [BankCodeController::class, 'bankCodesByPaymentCategory']);
    Route::get('/payment/categories', [PaymentCategoryController::class, 'playerListPaymentCategories']);

    Route::get("/transfer/{game_item}/balance/get", [TransferWalletController::class, 'getGameBalance']);
    Route::post("/transfer/{game_item}/deposit", [TransferWalletController::class, 'depositPoints']);
    Route::post("/transfer/{game_item}/withdraw", [TransferWalletController::class, 'withdrawPoints']);
    Route::get("/balances/details", [TransferWalletController::class, 'getAllGameBalances']);

    Route::post('/generate-otp', [OTPController::class, 'generateOTP']);
    Route::post('/verify-otp', [OTPController::class, 'verifyOTP']);

    /*
    Author: Shawn Dias
    Date:15/04/2024
    Description:  level routes
    */
    //==========================================================================
    //player 
    Route::get('level/getUserLevel', [LevelController::class, 'getUserLevel']);
    Route::get('level/checkLevelBasedOnPoints', [LevelController::class, 'checkLevelBasedOnPoints']);
    //==========================================================================

    
    
    // User Notification
    Route::get('{playerId}/notifications', [NotificationController::class, 'getPlayerNotifications']);
    Route::get('{playerId}/notifications/count', [NotificationController::class, 'getPlayerNotificationsCount']);
    Route::put('{playerId}/notifications/markAsRead', [NotificationController::class, 'markPlayerNotificationsAsRead']);

    // Player betting rounds 
    
    Route::prefix('bet_rounds')->group(function () {
        Route::get('/list', [BetRoundController::class, 'Bettings']);
    });

    //Turnover check
    Route::get('getTurnOverData',[WithdrawController::class, 'getTurnoverDetails']);
});

Route::post('/make-transaction', [TransactionController::class, 'createTransaction']);

Route::post('payment/providers/deposit/{service}/{transaction}', [DepositController::class, 'depositCallback']);
Route::post('payment/providers/withdraw/{service}/{transaction}', [WithdrawController::class, 'withdrawCallback']);

Route::prefix('providers')->group(function () {

    Route::prefix('3sing/{currency}')->middleware(['ss.provider.ip.check', 'ss.provider.auth.check'])->group(function () {
        Route::match(['get', 'post'], '/{wallet_action}', [SSController::class, 'walletAccess'])
            ->where('currency', SSCurrencyEnums::getValidationPattern())
            ->where('wallet_action', SSActionsEnums::getValidationPattern());
    });


    Route::prefix('pinnacle/{currency}')->middleware(['pinnacle.provider.ip.check', 'pinnacle.provider.auth.check'])->group(function () {
        Route::match(['get', 'post'], '/ping', [PinnacleController::class, 'walletAccess'])
            ->where('currency', PinnacleCurrencyEnums::getValidationPattern())
            ->name(PinnacleActionsEnums::PING->value);

        Route::match(['get', 'post'], '/{agentcode}/wallet/usercode/{usercode}/balance', [PinnacleController::class, 'walletAccess'])
            ->where('currency', PinnacleCurrencyEnums::getValidationPattern())
            ->name(PinnacleActionsEnums::BALANCE->value);

        Route::match(['get', 'post'], '/{agentcode}/wagering/usercode/{usercode}/request/{requestid}', [PinnacleController::class, 'walletAccess'])
            ->where('currency', PinnacleCurrencyEnums::getValidationPattern())
            ->name(PinnacleActionsEnums::WAGERING->value);
    });

    Route::prefix('OneAPI/{currency}/wallet')->middleware(['one.provider.ip.check', 'one.provider.auth.check'])->group(function () {
        Route::match(['get', 'post'], '/{wallet_action}', [ONEController::class, 'walletAccess'])
            ->where('currency', ONECurrencyEnums::getValidationPattern())
            ->where('wallet_action', ONEActionsEnums::getValidationPattern());
    });

    Route::prefix('ds88/{currency}')->middleware(['ds88.provider.ip.check', 'ds88.provider.auth.check'])->group(function () {
        Route::match(['get', 'post'], '/{wallet_action}/{account?}', [DS88Controller::class, 'walletAccess'])
            ->where('currency', DS88CurrencyEnums::getValidationPattern())
            ->where('wallet_action', DS88ActionsEnums::getValidationPattern());
    });

    Route::prefix('cmd/{currency}')->middleware(['cmd.provider.ip.check'])->group(function () {
        Route::match(['get', 'post'], '/{wallet_action}', [CMDController::class, 'walletAccess'])
            ->where('currency', CMDCurrencyEnums::getValidationPattern())
            ->where('wallet_action', CMDActionsEnums::getValidationPattern());
    });

    Route::prefix('evo/{currency}')->middleware(['evo.provider.ip.check', 'evo.provider.auth.check'])->group(function () {
        Route::post('/{wallet_action}', [EVOController::class, 'walletAccess'])
            ->where('currency', EVOCurrencyEnums::getValidationPattern())
            ->where('wallet_action', EVOActionsEnums::getValidationPattern());
    });

    Route::prefix('ug/{currency}')->middleware(['ug.provider.ip.check', 'ug.provider.auth.check'])->group(function () {
        Route::post('/{wallet_action}', [UGController::class, 'walletAccess'])
            ->where('currency', UGCurrencyEnums::getValidationPattern())
            ->where('wallet_action', UGActionsEnums::getValidationPattern());
    });

    Route::prefix('via/{currency}')->middleware(['via.provider.ip.check', 'via.provider.auth.check'])->group(function () {
        Route::get('/balance/player', [VIAController::class, 'getPlayerBalance'])->where('currency', VIACurrencyEnums::getValidationPattern());
        Route::post('/v2/balance/change', [VIAController::class, 'changePlayerBalance'])->where('currency', VIACurrencyEnums::getValidationPattern());
    });

    Route::prefix('km')->middleware(['km.provider.ip.check', 'km.provider.auth.check'])->group(function () {
        Route::post('/wallet/balance', [KMController::class, 'getPlayerBalance']);
        Route::post('/wallet/debit', [KMController::class, 'debitPlayerBalance']);
        Route::post('/wallet/credit', [KMController::class, 'creditPlayerBalance']);
        Route::post('/wallet/reward', [KMController::class, 'rewardPlayerBalance']);
    });

    Route::prefix('awc/{currency}')->middleware(['awc.provider.ip.check', 'awc.provider.auth.check'])->group(function () {
        Route::post('/wallet/access', [AWCController::class, 'walletAccess'])->where('currency', AWCCurrencyEnums::getValidationPattern());
    });

    Route::prefix('saba/{currency}')->middleware(['saba.provider.ip.check', 'saba.provider.auth.check', 'decompress.request'])->group(function () {
        Route::post('/{wallet_access}', [SABAController::class, 'walletAccess'])
            ->where('currency', SABACurrencyEnums::getValidationPattern())
            ->where('wallet_access', 'getBalance|placebet|confirmbet|cancelbet|settle|resettle|unsettle|placebetparlay|confirmbetparlay');
    });

    // Gemini 
    Route::prefix('gemini/{currency}')->middleware(['gemini.provider.ip.check', 'gemini.provider.auth.check'])->group(function () {
        Route::post('/operator/player/info',                [GeminiController::class, 'getPlayerInfo']);
        Route::post('/operator/player/wallet',              [GeminiController::class, 'checkBalance']);
        Route::post('/operator/transaction/transfer',       [GeminiController::class, 'playerTransfer']);
    });
});

Route::prefix('agent')->group(function () {
    Route::put('/{agent}/password/reset', [AgentController::class, 'passwordReset']);
    Route::post('/create', [AgentController::class, 'create']);
    Route::post('/login', [AgentController::class, 'login']);
});


Route::prefix('agent')->middleware(['auth:sanctum', 'scope.agent'])->group(function () {
    // module routes
    Route::get('playersList',[AgentController::class, 'getPlayersListedByAgent']);
    Route::get('playerTransactionList',[AgentController::class, 'getPlayerTransactionsList']);
    Route::get('playerBetRecords',[AgentController::class, 'getPlayerBettingList']);

    // dashboard card routes
    Route::get('getStatsData',[AgentController::class, 'getStatsData']);

    // chart routes
    Route::get('chart/registeredUsersFirstDeposit',[AgentController::class, 'getRegisteredUsersFirstDepositChart']);
    Route::get('chart/withdrawalDepositChart',[AgentController::class, 'getWithdrawalDepositChart']);
    Route::get('chart/winLossChart',[AgentController::class, 'getWinLossChart']);
    Route::get('chart/allStatsChart',[AgentController::class, 'getAllStatsChart']);
});

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login']);
});

/**
 * Admin routes
 * middleware: auth:sanctum, scope.admin, WhitelistIPMiddleware
 */

Route::group(['middleware' => ['auth:sanctum', 'scope.admin', 'whitelist.ip', 'compress.response']], function () {

    Route::prefix('admin')->middleware(['cors'])->group(function () {
        Route::get('/bank/codes', [BankCodeController::class, 'dropDownAdmin']);
        Route::post('/upload/file', [GeneralController::class, 'uploadFile']);
        Route::post('/create', [AdminController::class, 'create']);
        Route::put('/edit/{admin}', [AdminController::class, 'edit']);
        Route::get('/create/2fa', [AdminController::class, 'createTwoFactorAuth']);
        Route::post('/2fa/first/check', [AdminController::class, 'firstOTPCheck']);
        Route::post('/disable/2fa', [AdminController::class, 'disableTwoFactorAuth']);
        Route::get('/players/latest', [PlayerController::class, 'listLatestPlayers']);
        Route::post('/player/store', [PlayerController::class, 'store']);
        Route::put('/player/{player}/update', [PlayerController::class, 'update']);
        Route::get('/players', [PlayerController::class, 'listPlayers']);
        Route::get('/players/login-history', [LoginHistoryController::class, 'listPlayersLoginHistory']);
        Route::get('/company/banks/history', [PaymentMethodHistoryController::class, 'index']);
        Route::get('/login-history', [LoginHistoryController::class, 'listAdminsLoginHistory']);
        Route::get('player/{player}/view', [PlayerController::class, 'view']);
        Route::get('/player/{player}/history/login/latest', [PlayerController::class, 'latestLogin']);
        Route::put('/player/{player}/status/toggle', [PlayerController::class, 'toggleStatus']);
        Route::put('/player/{player}/withdraw/toggle', [PlayerController::class, 'toggleWithdraw']);
        Route::put('/player/{player}/betting/toggle', [PlayerController::class, 'toggleBetting']);
        Route::put('/player/{player}/deposit/toggle', [PlayerController::class, 'toggleDeposit']);
        Route::put('/player/{player}/agent/change', [PlayerController::class, 'changeAgent']);
        Route::put('/player/{player}/balance/adjust', [PlayerController::class, 'adjustBalance']);
        Route::get('/players/balance/history', [PlayerBalanceHistoryController::class, 'index']);
        Route::get('/players/game/transaction/history', [GameTransactionHistoryController::class, 'index']);
        Route::get('/players/game/access/history', [GameAccessHistoryController::class, 'index']);
        Route::put('/agent/{agent}/update', [AgentController::class, 'update']);
        Route::delete('/agent/{agent}/delete', [AgentController::class, 'destroy']);
        Route::get('/agent-histories', [AgentChangeHistoryController::class, 'index']);
        Route::get('/agents/get/all', [AgentController::class, 'index']);
        // lock and unlock function api's
        Route::put('/deposit/{id}/fa/lock/unlock', [DepositController::class, 'faLockDeposit']);
        Route::put('/withdraw/{id}/fa/lock/unlock', [WithdrawController::class, 'faLockWithdraw']);
        Route::put('/withdraw/{id}/risk/lock/unlock', [WithdrawController::class, 'riskLockWithdraw']);

        Route::get('/agents/normal', [AgentController::class, 'listNormalAgents']);
        Route::get('/agents/superior', [AgentController::class, 'listSuperiorAgents']);
        Route::post('/promotions/store', [PromotionController::class, 'store']);
        Route::delete('/promotions/{promotion}/delete', [PromotionController::class, 'delete']);
        Route::put('/promotions/{promotion}/update', [PromotionController::class, 'update']);
        Route::get('/promotions', [PromotionController::class, 'index']);
        Route::put('/promotions/{promotion}/toggle/status', [PromotionController::class, 'toggleStatus']);
        Route::get('/list/admins', [AdminController::class, 'listAdmins']);
        Route::put('/status/toggle/{admin}', [AdminController::class, 'toggleStatus']);
        Route::get('/get/pending/transactions/count', [TransactionController::class, 'getPendingTransactionsCount']);
        Route::get('/transactions', [TransactionController::class, 'listTransactions']);
        Route::get('/transactions/statuses', [TransactionController::class, 'getStatuses']);
        Route::get('/transactions/{transaction}', [TransactionController::class, 'adminView']);
        Route::put('/transactions/{transaction}/deposit', [DepositController::class, 'approveRejectDeposit']);
        Route::put('/transactions/{transaction}/withdraw', [WithdrawController::class, 'approveRejectWithdraw']);
        Route::put('/transactions/{transaction}/processing', [TransactionController::class, 'markTransactionStatus']);
        Route::put('/transactions/{transaction}/risk', [WithdrawController::class, 'approveRejectRisk']);
        Route::get('transactions/report', [TransactionController::class, 'getDailyReport']);

        Route::prefix('win_loss/purchases')->group(function () {
            Route::get('/', [WinLossPurchaseController::class, 'index']);
            Route::post('/store', [WinLossPurchaseController::class, 'store']);
            Route::put('/update/{win_loss_purchase}', [WinLossPurchaseController::class, 'update']);
            Route::delete('/delete/{win_loss_purchase}', [WinLossPurchaseController::class, 'destroy']);
        });

        // Notification 
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/create', [NotificationController::class, 'create']);
        Route::get('/notifications/edit/{id}', [NotificationController::class, 'edit']);
        Route::delete('/notifications/delete/{id}', [NotificationController::class, 'delete']);
        Route::put('/notifications/update/{id}', [NotificationController::class, 'update']);
        Route::get('/getplayers', [NotificationController::class, 'getPlayer']);

         // Top-trending category 
         Route::prefix('/trending-categories')->group(function () {
            Route::get('/', [TrendingCategoryController::class, 'index']);
            Route::post('/store', [TrendingCategoryController::class, 'create']);
            Route::get('/edit/{id}', [TrendingCategoryController::class, 'edit']);
            Route::put('/update/{id}', [TrendingCategoryController::class, 'update']);
            Route::delete('/delete/{id}', [TrendingCategoryController::class, 'destroy']);
        });

        Route::get('/logs/admin', [AdminLogController::class, 'index']);

        // TODO::for testing purpose will remove after testing is done
        Route::get('turnover/{id}',[WithdrawController::class, 'turnoverCheck']);
        Route::get('player/{playerId}/statistics', [PlayerController::class, 'getPlayerStatistics']);
        //
    });

    Route::prefix('dashboard')->group(function () {
        Route::get('/cards/stats', [DashboardController::class, 'getDashboardStats']);
        Route::get('/monthly/transaction', [DashboardController::class, 'getMonthlyTransaction']);
        Route::get('/daily/transaction', [DashboardController::class, 'getDailyCurrentMonthTransaction']);
    });

    Route::prefix('bet_rounds')->group(function () {
        Route::get('/list', [BetRoundController::class, 'index']);
        Route::get('/{bet_round}/view', [BetRoundController::class, 'view']);
        Route::get('/monthly/winloss', [BetRoundController::class, 'getMonthWinLoss']);
    });

    Route::prefix('user/payment/method')->group(function () {
        Route::post('/store', [UserPaymentMethodController::class, 'store']);
        Route::put('/update/{user_payment_method}', [UserPaymentMethodController::class, 'update']);
        Route::put('/status/toggle/{user_payment_method}', [UserPaymentMethodController::class, 'toggleStatus']);
        Route::delete('/delete/{user_payment_method}', [UserPaymentMethodController::class, 'delete']);
    });

    Route::prefix('permissions')->group(function () {
        Route::get('/index', [PermissionController::class, 'index']);
        Route::post('/store', [PermissionController::class, 'store']);
        Route::put('/update/{id}', [PermissionController::class, 'update']);
        Route::delete('/delete/{id}', [PermissionController::class, 'delete']);
        Route::post('category/store', [PermissionCategoryController::class, 'store']);
    });

    Route::prefix('ip/white/list')->group(function () {
        Route::get('/', [WhitelistIPController::class, 'index']);
        Route::get('/types', [WhitelistIPController::class, 'getTypes']);
        Route::post('/store', [WhitelistIPController::class, 'store']);
        Route::put('/{whitelist_ip}/update/', [WhitelistIPController::class, 'update']);
        Route::delete('/delete/{id}', [WhitelistIPController::class, 'delete']);
    });


    Route::prefix('roles')->group(function () {
        Route::get('/index', [RoleController::class, 'index']);
        Route::post('/store', [RoleController::class, 'store']);
        Route::put('/update/{id}', [RoleController::class, 'update']);
        Route::delete('/delete/{id}', [RoleController::class, 'delete']);
    });

    Route::prefix('game_platforms')->group(function () {
        Route::post('/create', [GamePlatformController::class, 'create']);
        Route::post('/{game_platform}/update', [GamePlatformController::class, 'update']);
        Route::get('/', [GamePlatformController::class, 'index']);
        Route::get('/dropdown', [GamePlatformController::class, 'dropdown']);
        Route::get('/game_codes/{provider}/get', [GamePlatformController::class, 'getGameCodePlatform']);
        Route::delete('{game_platform}/delete', [GamePlatformController::class, 'delete']);
    });

    Route::prefix('game_categories')->group(function () {
        Route::post('/create', [GameCategoryController::class, 'create']);
        Route::post('/{game_category}/update', [GameCategoryController::class, 'update']);
        Route::get('/properties', [GameCategoryController::class, 'listProperties']);
        Route::get('/', [GameCategoryController::class, 'index']);
        Route::get('/all', [GameCategoryController::class, 'getAll']);

        Route::get('/{game_category}/get', [GameCategoryController::class, 'getGameCategory']);
        Route::delete('{game_category}/delete', [GameCategoryController::class, 'delete']);
        Route::put('{game_category}/status/toggle', [GameCategoryController::class, 'toggleStatus']);
        Route::put('/order/change', [GameCategoryController::class, 'changeOrder']);
        Route::put('{game_category}/games/order/change', [GameCategoryController::class, 'changeGamesOrder']);
    });

    Route::prefix('promotion_categories')->group(function () {
        Route::post('/create', [PromotionCategoryController::class, 'create']);
        Route::put('/{promotion_category}/update', [PromotionCategoryController::class, 'update']);
        Route::get('/', [PromotionCategoryController::class, 'index']);
        Route::delete('{promotion_category}/delete', [PromotionCategoryController::class, 'delete']);
        Route::put('{promotion_category}/status/toggle', [PromotionCategoryController::class, 'toggleStatus']);
        Route::put('/order/change', [PromotionCategoryController::class, 'changeOrder']);
        Route::put('{promotion_category}/promotion/order/change', [PromotionCategoryController::class, 'changePromotionOrder']);
    });

    Route::prefix('game_items')->group(function () {
        Route::post('/create', [GameItemController::class, 'create']);
        Route::post('/{game_item}/update', [GameItemController::class, 'update']);
        Route::get('/properties', [GameItemController::class, 'listProperties']);
        Route::get('/', [GameItemController::class, 'index']);
        Route::put('{game_item}/status/change', [GameItemController::class, 'changeStatus']);
        // Route::delete('{game_item}/delete', [GameItemController::class, 'delete']);
    });

    Route::prefix('players/notification')->group(function () {
        Route::post('/create', [PlayerNotificationController::class, 'create']);
        Route::put('/{notification}/update', [PlayerNotificationController::class, 'update']);
        Route::get('/', [PlayerNotificationController::class, 'index']);
        Route::delete('/delete/all/read', [PlayerNotificationController::class, 'deleteAllRead']);
        Route::delete('/delete/player/read', [PlayerNotificationController::class, 'deletePlayerRead']);
    });

    Route::prefix('payment/method')->group(function () {
        Route::post('/store', [PaymentMethodController::class, 'store']);
        Route::get('/', [PaymentMethodController::class, 'index']);
        Route::post('/{payment_method}/update', [PaymentMethodController::class, 'update']);
        Route::put('/{payment_method}/deposit/toggle', [PaymentMethodController::class, 'toggleDeposit']);
        Route::put('/{payment_method}/withdraw/toggle', [PaymentMethodController::class, 'toggleWithdraw']);
        Route::put('/{payment_method}/maintenance/toggle', [PaymentMethodController::class, 'toggleMaintenance']);
        Route::get('/daily/report', [PaymentMethodHistoryController::class, 'getDailyReport']);
        Route::post('/adjust', [PaymentMethodController::class, 'adjust']);
        Route::get('/drop/down', [PaymentMethodController::class, 'dropDown']);
        Route::put('{id}/default', [PaymentMethodController::class, 'updateDefault']);

    });

    Route::prefix('bank/code')->group(function () {
        Route::post('/store', [BankCodeController::class, 'store']);
        Route::get('/constants', [BankCodeController::class, 'getConstants']);
        Route::get('/', [BankCodeController::class, 'index']);
        Route::post('/{bank_code}/update', [BankCodeController::class, 'update']);
        Route::put('/{bank_code}/status/toggle', [BankCodeController::class, 'toggleStatus']);
        Route::put('/{bank_code}/display_players/toggle', [BankCodeController::class, 'toggleDisplayForPlayers']);
    });

    Route::prefix('payment/category')->group(function () {
        Route::get('/drop/down', [PaymentCategoryController::class, 'dropDown']);
        Route::post('/store', [PaymentCategoryController::class, 'store']);
        Route::get('/', [PaymentCategoryController::class, 'index']);
        Route::post('/{payment_category}/update', [PaymentCategoryController::class, 'update']);
        Route::put('/{payment_category}/status/toggle', [PaymentCategoryController::class, 'toggleStatus']);
        Route::delete('{payment_category}/delete', [PaymentCategoryController::class, 'delete']);
    });

    Route::resource('/setting', SettingController::class);
    Route::resource('/player_rating', PlayerRatingController::class);

    /*
    Author: Shawn Dias
    Date:15/04/2024
    Description: Creating CRUD routes for a new feature levels
    */
    //==========================================================================
    Route::prefix('level')->group(function () {

        //Crud
        Route::get('/getAllLevels', [LevelController::class, 'viewAllLevels']);
        Route::post('/createLevel', [LevelController::class, 'createLevel']);
        Route::put('/updateLevel/{id}', [LevelController::class, 'updateLevel']);
        Route::delete('/deleteLevel/{id}', [LevelController::class, 'deleteLevel']);

        //player 
        Route::get('/getUserLevel', [LevelController::class, 'getUserLevel']);
        Route::get('/checkLevelBasedOnPoints', [LevelController::class, 'checkLevelBasedOnPoints']);
    });

    //==========================================================================
  

});
