<?php

namespace App\Services\Providers\UGProvider;

use Exception;
use Carbon\Carbon;
use App\Models\Bet;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Models\Player;
use GuzzleHttp\Client;
use App\Models\BetRound;
use App\Models\GameItem;
use App\Models\GamePlatform;
use App\Constants\BetConstants;
use App\Models\UGProviderConfig;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Log;
use App\Models\PlayerBalanceHistory;
use Illuminate\Support\Facades\Http;
use App\Models\GameTransactionHistory;
use Illuminate\Support\Facades\Config;
use App\Constants\GamePlatformConstants;
use App\Constants\UGProviderConfigConstants;
use App\Services\Providers\ProviderInterface;
use App\Constants\GameTransactionHistoryConstants;
use App\Services\Providers\UGProvider\DTOs\UGConfigDTO;
use App\Services\Providers\UGProvider\Enums\UGActionsEnums;
use App\Services\Providers\UGProvider\Enums\UGCurrencyEnums;
use App\Services\Providers\UGProvider\DTOs\UGSeamlessResponseDTO;
use App\Services\Providers\UGProvider\Encryption\AesCbcEncryptor;

class UGProvider implements ProviderInterface
{
    // status description
    const STATUS_DESC_SUCCESS_RESPONSE = 'SUCCESS';
    const STATUS_DESC_UNKNOWN_ERROR = 'UNKNOWN ERROR';
    const STATUS_DESC_AUTH_FAILED = 'API KEY ERROR';
    const STATUS_DESC_IP_NOT_ALLOWED = 'IP NOT IN THE WHITELIST';
    const STATUS_DESC_VALIDATION_ERROR = 'ENTER PARAMETER ERROR';
    const STATUS_DESC_USER_NOT_FOUND = 'MEMBER ACCOUNT IS NOT EXIST';
    const STATUS_DESC_CURRENCY_MISMATCH = 'NOT ALLOWED CURRENCY';
    const STATUS_DESC_INSUFFICIENT_FUNDS = 'INSUFFICIENT BALANCE';
    const STATUS_DESC_BET_DOES_NOT_EXIST = 'BET DOES NOT EXIST';
    const STATUS_DESC_TRANSACTION_DOES_NOT_EXIST = 'TRANSACTION_DOES_NOT_EXIST';
    const STATUS_DESC_WALLET_UPDATE_FAIL = 'WALLET UPDATE FAIL';
    const STATUS_DESC_GAME_ITEM_NOT_FOUND = 'GAME_ITEM_NOT_FOUND';
    const STATUS_DESC_ACTION_NOT_SUPPORTED = 'INVALID URL';
    const STATUS_DESC_SESSION_NOT_EXIST = 'SESSION_NOT_EXIST';
    const STATUS_DESC_CHANGE_TYPE_NOT_SUPPORTED = 'CHANGE TYPE NOT SUPPORTED';

    // status 
    const STATUS_SUCCESS_RESPONSE = '000000';
    const STATUS_UNKNOWN_ERROR = '999999';
    const STATUS_AUTH_FAILED = '000004';
    const STATUS_VALIDATION_ERROR = '000007';
    const STATUS_IP_NOT_ALLOWED = '000003';
    const STATUS_CURRENCY_MISMATCH = '100002';
    const STATUS_USER_NOT_FOUND = '100001';
    const STATUS_INSUFFICIENT_FUNDS = '300004';
    const STATUS_WALLET_UPDATE_FAIL = '100008';
    const STATUS_ACTION_NOT_SUPPORTED = '000009';
    const STATUS_SESSION_NOT_EXIST = '000014';

    //change types 
    const CHANGE_TYPE_BETTING = 'BET';
    const CHANGE_TYPE_INSURANCE = 'INS';
    const CHANGE_TYPE_JACKPOT_PAYOUT = 'JP';

    // group commissions
    const GROUP_COMMISSIONS_A_GROUP = 'a'; // Standard 0.96
    const GROUP_COMMISSIONS_B_GROUP = 'b'; // -0.01
    const GROUP_COMMISSIONS_C_GROUP = 'c'; // -0.02
    const GROUP_COMMISSIONS_D_GROUP = 'd'; // -0.03
    const GROUP_COMMISSIONS_E_GROUP = 'e'; // -0.04
    const GROUP_COMMISSIONS_F_GROUP = 'f'; // -0.05
    const GROUP_COMMISSIONS_G_GROUP = 'g'; // -0.06
    const GROUP_COMMISSIONS_H_GROUP = 'h'; // -0.07
    const GROUP_COMMISSIONS_I_GROUP = 'i'; // -0.08
    const GROUP_COMMISSIONS_J_GROUP = 'j'; // -0.09

    // languages
    const LANG_EN = 'en';
    const LANG_VN = 'vi';

    protected $user_id;
    protected $operator_id;
    protected $login_name;
    protected $return_url;
    protected $language;
    protected $currency;
    protected $odds_expression;
    protected $theme;
    protected $template;
    protected $favorite_sport;
    protected $token;
    protected $game_mode;
    protected $default_market;
    protected $api_key;
    protected $group_commission;
    protected $base_url;
    protected $headers;
    protected $login_base_url;

    function __construct(Player $player, $game_code)
    {
        $this->login_name = $player->user->user_name;
        $this->user_id = $player->user->user_name;

        $this->currency = self::getGameCurrency($player->wallet->currency);
        $credentials = self::getCredential($this->currency);
        $this->base_url = $credentials['base_url'];
        $this->login_base_url = $credentials['login_base_url'];
        $this->operator_id = $credentials['operator_id'];
        $this->api_key = $credentials['api_key'];
        $this->return_url = $credentials['return_url'];

        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $this->language = self::getGameLanguage($player->language);
        $this->group_commission = self::GROUP_COMMISSIONS_A_GROUP;

        // $config = self::getUGconfig($player);
        // $this->odds_expression = $config->odds_expression;
        // $this->template = $config->template;
        // $this->theme = $config->theme;
        // $this->game_mode = $config->game_mode;
        // $this->favorite_sport = $config->favorite_sport;
        // $this->default_market = $config->default_market;

        $tokens = $player->user->tokens;
        $first_token = $tokens->first();
        $this->token = hash('sha256', $this->user_id . $first_token->token . $this->api_key);
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        try {

            $web_type = match ($deviceType) {
                'Mobile' => 'mobile',
                default => 'pc'
            };

            $login_query = [
                'operatorId' => $this->operator_id,
                'userId' => $this->user_id,
                'returnUrl' => $this->return_url,
                // 'oddsExpression' => $this->odds_expression,
                'language' => $this->language,
                'webType' => $web_type,
                // 'theme' => $this->theme,
                // 'template' => $this->template,
                // 'sportId' => $this->favorite_sport,
                'token' => $this->token,
                // 'gameMode' => $this->game_mode,
                // 'defaultDisplay' => $this->default_market,
            ];

            $queryString = http_build_query($login_query);

            $urlToSendToFrontend = $this->login_base_url . '/auth/single?' . $queryString;

            $data = [
                'apiKey' => $this->api_key,
                'operatorId' => $this->operator_id,
                'userId' => $this->user_id,
            ];

            $check_status_response = Http::withHeaders($this->headers)->post($this->base_url . '/api/single/getMemberStatus', $data);

            $check_status_response = $check_status_response->json();

            $is_user = false;

            $login_url = null;

            if (isset($check_status_response['data']['statusCode']) && $check_status_response['data']['statusCode'] == 1) {

                $logout_response = Http::withHeaders($this->headers)->post($this->base_url . '/api/single/logout', $data);

                $logout_response = $logout_response->json();

                $is_user = true;

                $login_url = $urlToSendToFrontend;
            }

            return json_encode([
                'login_url' => $login_url,
                'is_user' => $is_user,
            ]);

            // return json_encode([
            //     'logout_api_url' => $this->base_url . '/api/single/logout',
            //     'check_status_api_url' => $this->base_url . '/api/single/getMemberStatus',
            //     'request_headers' => $this->headers,
            //     'request_data' => $data,
            //     'logout_response' => $logout_response ?? null,
            //     'check_status_response' => $check_status_response,
            //     'login_url' => $urlToSendToFrontend,
            //     'is_user' => $is_user,
            // ]);

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('UG Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        try {

            $data = [
                'apiKey' => $this->api_key,
                'operatorId' => $this->operator_id,
                'userId' => $this->user_id,
                'loginName' => $this->login_name,
                'currencyId' => $this->currency,
                // 'groupCommission' => $this->group_commission,
            ];

            $response = Http::withHeaders($this->headers)->post($this->base_url . '/api/single/register', $data);

            return $response->body();
            // return json_encode([
            //     'api_url' => $this->base_url . '/api/single/register',
            //     'request_headers' => $this->headers,
            //     'request_data' => $data,
            //     'response' => $response->body()
            // ]);

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('UG Provider Call registerToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public static function getUGconfig(player $player): UGConfigDTO
    {
        if ($player->ugProviderConfig) {

            $UGconfig = $player->ugProviderConfig;
        } else {

            $UGconfig = $player->ugProviderConfig()->create([
                'odds_expression' => UGProviderConfigConstants::ODDS_EXPRESSION_DECIMAL,
                'template' => UGProviderConfigConstants::TEMPLATE_STANDARD,
                'theme' => UGProviderConfigConstants::THEME_CLASSIC_BLUE,
                'game_mode' => UGProviderConfigConstants::GAME_MODE_ALL_SPORTS,
                'favorite_sport' => UGProviderConfigConstants::FAVORITE_SPORT_SOCCER,
                'default_market' => UGProviderConfigConstants::DEFAULT_MARKET_FAST,
            ]);
        }

        if ($player->wallet->currency == GlobalConstants::CURRENCY_VNDK) {
            return new UGConfigDTO(
                $UGconfig->odds_expression,
                $UGconfig->template,
                $UGconfig->theme,
                $UGconfig->game_mode,
                $UGconfig->favorite_sport,
                $UGconfig->default_market,
            );
        }

        if ($player->wallet->currency == GlobalConstants::CURRENCY_INR) {
            return new UGConfigDTO(
                $UGconfig->odds_expression,
                $UGconfig->template,
                $UGconfig->theme,
                $UGconfig->game_mode,
                $UGconfig->favorite_sport,
                $UGconfig->default_market,
            );
        }

        if ($player->wallet->currency == GlobalConstants::CURRENCY_PHP) {
            return new UGConfigDTO(
                $UGconfig->odds_expression,
                $UGconfig->template,
                $UGconfig->theme,
                $UGconfig->game_mode,
                $UGconfig->favorite_sport,
                $UGconfig->default_market,
            );
        }

        throw new Exception('UG Config Error');
    }

    public static function getGameLanguage($language)
    {
        return match ($language) {
            GlobalConstants::LANG_EN => self::LANG_EN,
            GlobalConstants::LANG_VN => self::LANG_VN,
            default => self::LANG_EN,
        };
    }

    public static function getCredential(UGCurrencyEnums $ug_currency)
    {
        $operator_id = null;
        $api_key = null;
        $return_url = null;

        switch ($ug_currency) {
            case UGCurrencyEnums::VNDK:
                $operator_id = Config::get('app.ug_operator_id.vndk');
                $api_key = Config::get('app.ug_api_key.vndk');
                $return_url = Config::get('app.ug_return_url.vndk');
                break;
            case UGCurrencyEnums::PHP:
                $operator_id = Config::get('app.ug_operator_id.php');
                $api_key = Config::get('app.ug_api_key.php');
                $return_url = Config::get('app.ug_return_url.php');
                break;
            case UGCurrencyEnums::INR:
                $operator_id = Config::get('app.ug_operator_id.inr');
                $api_key = Config::get('app.ug_api_key.inr');
                $return_url = Config::get('app.ug_return_url.inr');
                break;
        }

        return [
            'base_url' => Config::get('app.ug_base_url'),
            'login_base_url' => Config::get('app.ug_login_base_url'),
            'operator_id' => $operator_id,
            'api_key' => $api_key,
            'return_url' => $return_url,
        ];
    }

    public static function getSystemCurrency(UGCurrencyEnums $currency): int
    {
        return match ($currency) {
            UGCurrencyEnums::VNDK => GlobalConstants::CURRENCY_VNDK,
            UGCurrencyEnums::PHP => GlobalConstants::CURRENCY_PHP,
            UGCurrencyEnums::INR => GlobalConstants::CURRENCY_INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getGameCurrency($currency): UGCurrencyEnums
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => UGCurrencyEnums::VNDK,
            GlobalConstants::CURRENCY_PHP => UGCurrencyEnums::PHP,
            GlobalConstants::CURRENCY_INR => UGCurrencyEnums::INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getChangeTypes()
    {
        return [
            self::CHANGE_TYPE_BETTING,
            self::CHANGE_TYPE_INSURANCE,
            self::CHANGE_TYPE_JACKPOT_PAYOUT
        ];
    }

    public static function roundBalance($balance)
    {
        return round($balance, 4);
    }

    public static function authorizeProvider($api_pass, UGCurrencyEnums $ug_currency)
    {
        $credentials = self::getCredential($ug_currency);

        $encryptor = new AesCbcEncryptor($credentials['api_key'], $credentials['operator_id']);

        $decrypted_data = $encryptor->decrypt($api_pass);

        if ($decrypted_data !== false) {
            // Data was successfully decrypted
            $is_valid = $encryptor->validate($decrypted_data, 20); // Allow a tolerance of 20 seconds

            return $is_valid;
        }

        return false;
    }

    public static function unknownError(): UGSeamlessResponseDTO
    {
        return new UGSeamlessResponseDTO(self::unknownErrorResponse(), 200);
    }

    public static function authFailed(): UGSeamlessResponseDTO
    {
        return new UGSeamlessResponseDTO(self::authFailedResponse(), 200);
    }

    public static function validationError($error): UGSeamlessResponseDTO
    {
        return new UGSeamlessResponseDTO(self::validationErrorResponse($error), 200);
    }

    public static function ipNotAllowed($ip): UGSeamlessResponseDTO
    {
        return new UGSeamlessResponseDTO(self::ipNotAllowedResponse($ip), 200);
    }

    public static function walletAccess($request_data, UGActionsEnums $wallet_action, UGCurrencyEnums $currency): UGSeamlessResponseDTO
    {
        $game_item = GameItem::where('game_id', GamePlatformConstants::UG_GAME_CODE_LOBBY)->first();

        if (!$game_item) {

            return new UGSeamlessResponseDTO(self::gameItemNotFoundResponse(), 200);
        }

        return match ($wallet_action) {
            UGActionsEnums::LOGIN => self::checkLogin($request_data, $game_item, $currency),
            UGActionsEnums::GET_BALANCE => self::getBalance($request_data, $game_item, $currency),
            UGActionsEnums::CHANGE_BALANCE => self::changeBalance($request_data, $game_item, $currency),
            UGActionsEnums::CHECK_TRANSACTION => self::checkTransactions($request_data, $game_item, $currency),
            UGActionsEnums::CANCEL_TRANSACTION => self::rollBack($request_data, $game_item, $currency),
            default => new UGSeamlessResponseDTO(self::actionNotSupportedResponse(), 200),
        };
    }

    private static function checkTransactions($request_data, $game_item, UGCurrencyEnums $requested_currency): UGSeamlessResponseDTO
    {
        // get the all the user and their locked wallets here
        $data = $request_data['data'];

        $user_names = collect($data)->pluck('userId')->unique();

        $users = User::whereIn('user_name', $user_names)->with('player')->get()->keyBy('user_name');

        $result = [];

        foreach ($data as $record) {
            // check if the user exists
            $user = $users->get($record['userId']);

            if (!$user) {

                $result[] = self::userNotFoundTransactionalResponse($record['userId'], null, $record['txnId']);

                continue;
            }

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if ($player_game_currency !== $requested_currency) {

                $result[] = self::currencyMismatchTransactionalResponse($record['userId'], null, $record['txnId'], $locked_wallet->balance);

                continue;
            }

            $refer_transaction = $game_item->gameTransactionHistories()->referenceNo($record['txnId'])->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

            if (!$refer_transaction) {

                $result[] = self::invalidBetTransactionalResponse($record['userId'], null, $record['txnId'], $locked_wallet->balance);

                continue;
            }

            $result[] = self::successTransactionalResponse($record['userId'], null, $record['txnId'], $locked_wallet->balance);

            continue;
        }

        return new UGSeamlessResponseDTO(self::successResponse($result), 200);
    }

    private static function rollBack($request_data, $game_item, UGCurrencyEnums $requested_currency): UGSeamlessResponseDTO
    {
        // get the all the user and their locked wallets here
        $data = $request_data['data'];

        $user_names = collect($data)->pluck('userId')->unique();

        $users = User::whereIn('user_name', $user_names)->with('player')->get()->keyBy('user_name');

        $result = [];

        foreach ($data as $record) {
            // check if the user exists
            $user = $users->get($record['userId']);

            if (!$user) {

                $result[] = self::userNotFoundTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId']);

                continue;
            }

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            // init the game transaction history 
            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $record['txnId'],
                null,
                $game_item->gamePlatform->id
            );

            // init the balance history
            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            // check the currency
            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if ($player_game_currency !== $requested_currency) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    0,
                    true,
                    self::STATUS_DESC_CURRENCY_MISMATCH,
                );

                $result[] = self::currencyMismatchTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                continue;
            }

            $player_bet = $game_item->bets()
                ->statusIn([BetConstants::STATUS_SETTLED, BetConstants::STATUS_UNSETTLED, BetConstants::STATUS_RESETTLED])
                ->reference($record['ticketId'])
                ->playerId($locked_wallet->player_id)
                ->first();

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    0,
                    false,
                    self::STATUS_DESC_BET_DOES_NOT_EXIST,
                );

                $result[] = self::invalidBetTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                continue;
            }

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            if ($refer_transaction?->reference_no != $record['txnId']) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    0,
                    false,
                    self::STATUS_DESC_TRANSACTION_DOES_NOT_EXIST,
                );

                $result[] = self::invalidTransactionTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                continue;
            }

            if ($player_bet->status == BetConstants::STATUS_UNSETTLED) {

                $locked_wallet->credit($refer_transaction->points);

                $player_bet->cancel(now()->toDateTimeString());

                $player_bet->betRound->close(now()->toDateTimeString(), null);

                $game_transaction_history->gameActionSuccess(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                    $refer_transaction->points,
                    false,
                    $locked_wallet->balance,
                    GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION,
                    null,
                    $player_bet->id,
                    $refer_transaction->id,
                );

                $player_balance_history->gameActionSuccess(
                    $refer_transaction->points,
                    false,
                    $locked_wallet->balance,
                    $game_transaction_history->id,
                    GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION
                );

                $result[] = self::successTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                continue;
            } else {

                $locked_wallet->debit($refer_transaction->points);

                $bet_round = $player_bet->betRound;

                $player_bet->unsettle();

                $bet_round->reopen(null, $bet_round->total_turnovers, $bet_round->total_valid_bets, null);

                $game_transaction_history->gameActionSuccess(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_UNSETTLE,
                    $refer_transaction->points,
                    true,
                    $locked_wallet->balance,
                    GameTransactionHistoryConstants::NOTE_TYPE_UNSETTLE_BET,
                    null,
                    $player_bet->id,
                    $refer_transaction->id,
                );

                $player_balance_history->gameActionSuccess(
                    $refer_transaction->points,
                    true,
                    $locked_wallet->balance,
                    $game_transaction_history->id,
                    GameTransactionHistoryConstants::NOTE_TYPE_UNSETTLE_BET
                );

                $result[] = self::successTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                continue;
            }
        }

        return new UGSeamlessResponseDTO(self::successResponse($result), 200);
    }

    private static function changeBalance($request_data, $game_item, UGCurrencyEnums $requested_currency): UGSeamlessResponseDTO
    {
        // get the all the user and their locked wallets here
        $data = $request_data['data'];

        $user_names = collect($data)->pluck('userId')->unique();

        $users = User::whereIn('user_name', $user_names)->with('player')->get()->keyBy('user_name');

        $result = [];
        // in the loop handle the bets one by one if one failed put it's failed message in an array and continue (same as KM)
        foreach ($data as $record) {
            // check if the user exists
            $user = $users->get($record['userId']);

            if (!$user) {

                $result[] = self::userNotFoundTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId']);

                continue;
            }

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            // init the game transaction history 
            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $record['txnId'],
                null,
                $game_item->gamePlatform->id
            );

            // init the balance history
            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            // check the currency
            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if ($player_game_currency !== $requested_currency) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    $record['amount'],
                    true,
                    self::STATUS_DESC_CURRENCY_MISMATCH,
                );

                $result[] = self::currencyMismatchTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                continue;
            }

            // check the change type 
            $change_type = $record['changeType'];

            if (!in_array($change_type, self::getChangeTypes())) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    $record['amount'],
                    true,
                    self::STATUS_DESC_CHANGE_TYPE_NOT_SUPPORTED,
                );

                $result[] = self::changeTypeNotSupportedTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                continue;
            }
            // in all the following cases the bet record has to be unsettled

            // change type = BET
            if ($change_type === self::CHANGE_TYPE_BETTING) {

                // check if the bet is true check the player balance debit the player create bet and bet round save the stuff and continue
                if ($record['bet']) {

                    $debit_amount = abs($record['amount']);

                    if ($locked_wallet->balance < $debit_amount) {

                        $game_transaction_history->gameActionFailed(
                            GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                            $debit_amount,
                            true,
                            self::STATUS_DESC_INSUFFICIENT_FUNDS,
                        );

                        $result[] = self::insufficientFundsTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                        continue;
                    }

                    $locked_wallet->debit($debit_amount);

                    $bet_round = BetRound::begin(
                        $player->id,
                        $game_item->gamePlatform->id,
                        $record['ticketId'],
                        now()->toDateTimeString(),
                        $locked_wallet->currency,
                    );

                    $player_bet = Bet::place(
                        $debit_amount,
                        null,
                        $record['ticketId'],
                        $bet_round->id,
                        $game_item->id,
                        now()->toDateTimeString(),
                        $locked_wallet->currency,
                    );

                    $game_transaction_history->gameActionSuccess(
                        GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                        $debit_amount,
                        true,
                        $locked_wallet->balance,
                        GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET,
                        null,
                        $player_bet->id,
                    );

                    $player_balance_history->gameActionSuccess(
                        $debit_amount,
                        true,
                        $locked_wallet->balance,
                        $game_transaction_history->id,
                        GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET
                    );

                    $result[] = self::successTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                    continue;
                } else {
                    // check if the bet is false get the bet and bet round check if they exists credit the player balance, 
                    // calculate the winAmount winloss and all of the this then save the stuff

                    $player_bet = $game_item->bets()
                        ->statusIn([BetConstants::STATUS_SETTLED, BetConstants::STATUS_UNSETTLED, BetConstants::STATUS_RESETTLED])
                        ->reference($record['ticketId'])
                        ->playerId($locked_wallet->player_id)
                        ->first();

                    if (!$player_bet) {

                        $game_transaction_history->gameActionFailed(
                            GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                            $record['amount'],
                            false,
                            self::STATUS_DESC_BET_DOES_NOT_EXIST,
                        );

                        $result[] = self::invalidBetTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                        continue;
                    }

                    if ($player_bet->status == BetConstants::STATUS_UNSETTLED) {

                        $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

                        $locked_wallet->credit($record['amount']);

                        $bet_round = $player_bet->betRound;

                        $win_loss = $record['amount'] - $player_bet->bet_amount;

                        $player_bet->settle($record['amount'], now()->toDateTimeString());

                        $bet_round->close(
                            now()->toDateTimeString(),
                            $win_loss,
                            $player_bet->turnover,
                            $player_bet->valid_bet,
                            $record['amount'],
                        );

                        $game_transaction_history->gameActionSuccess(
                            GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                            $record['amount'],
                            false,
                            $locked_wallet->balance,
                            GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION,
                            null,
                            $player_bet->id,
                            $refer_transaction->id,
                        );

                        $player_balance_history->gameActionSuccess(
                            $record['amount'],
                            false,
                            $locked_wallet->balance,
                            $game_transaction_history->id,
                            GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION
                        );

                        $result[] = self::successTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                        continue;
                    } else {

                        $is_withdraw = false;
                        $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT;

                        if ($record['amount'] < 0) {
                            $is_withdraw = true;
                            $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT;
                        }

                        $amount = abs($record['amount']);

                        $is_withdraw ? $locked_wallet->debit($amount) : $locked_wallet->credit($amount);

                        $win_amount = $player_bet->win_amount + $amount;

                        if ($is_withdraw) {

                            $win_amount = $player_bet->win_amount - $amount;
                        }

                        $win_loss = $win_amount - $player_bet->bet_amount;

                        $player_bet->resettle(
                            $win_amount,
                            now()->toDateTimeString(),
                            $player_bet->bet_on,
                            $player_bet->rebate,
                            $player_bet->comm,
                            $player_bet->valid_bet,
                            $player_bet->turnover,
                            $player_bet->odds,
                            $player_bet->win_loss
                        );

                        $bet_round = $player_bet->betRound;

                        $bet_round->reclose(
                            now()->toDateTimeString(),
                            $win_loss,
                            $bet_round->device,
                            $bet_round->ip_address,
                            $bet_round->provider,
                            $bet_round->total_turnovers,
                            $bet_round->total_valid_bets,
                            $player_bet->win_amount
                        );

                        $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

                        $game_transaction_history->gameActionSuccess(
                            $type,
                            $amount,
                            $is_withdraw,
                            $locked_wallet->balance,
                            GameTransactionHistoryConstants::NOTE_RESETTLE_BET,
                            null,
                            $player_bet->id,
                            $refer_transaction?->id,
                        );

                        $player_balance_history->gameActionSuccess(
                            $amount,
                            $is_withdraw,
                            $locked_wallet->balance,
                            $game_transaction_history->id,
                            GameTransactionHistoryConstants::NOTE_RESETTLE_BET
                        );

                        $result[] = self::successTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                        continue;
                    }
                }
            }

            // change type = JP
            // todo check if the Jackpot is calculated in the win/loss 
            if ($change_type === self::CHANGE_TYPE_JACKPOT_PAYOUT) {
                // credit the player balance save the game transaction history and player balance history
                $locked_wallet->credit($record['amount']);

                $bet_round = BetRound::begin(
                    $player->id,
                    $game_item->gamePlatform->id,
                    $record['ticketId'],
                    now()->toDateTimeString(),
                    $locked_wallet->currency,
                );

                $player_bet = Bet::place(
                    $record['amount'],
                    null,
                    $record['ticketId'],
                    $bet_round->id,
                    $game_item->id,
                    now()->toDateTimeString(),
                    $locked_wallet->currency,
                );

                $player_bet->settle($record['amount'], now()->toDateTimeString());

                $bet_round->close(
                    now()->toDateTimeString(),
                    null,
                    $player_bet->turnover,
                    $player_bet->valid_bet,
                    $record['amount'],
                );

                $game_transaction_history->gameActionSuccess(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_JACKPOT,
                    $record['amount'],
                    false,
                    $locked_wallet->balance,
                    GameTransactionHistoryConstants::NOTE_TYPE_JACKPOT,
                    null,
                    $player_bet->id,
                );

                $player_balance_history->gameActionSuccess(
                    $record['amount'],
                    false,
                    $locked_wallet->balance,
                    $game_transaction_history->id,
                    GameTransactionHistoryConstants::NOTE_TYPE_JACKPOT
                );

                $result[] = self::successTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                continue;
            }

            // change type = INS
            if ($change_type === self::CHANGE_TYPE_INSURANCE) {

                // get the bet check if it exists
                $player_bet = $game_item->bets()->reference($record['ticketId'])->playerId($locked_wallet->player_id)->first();

                if (!$player_bet) {

                    $game_transaction_history->gameActionFailed(
                        GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                        $record['amount'],
                        false,
                        self::STATUS_DESC_BET_DOES_NOT_EXIST,
                    );

                    $result[] = self::invalidBetTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                    continue;
                }

                // check if you can deduct the amount they sent from the bet bet_amount
                if ($player_bet->valid_bet < $record['amount']) {

                    $game_transaction_history->gameActionFailed(
                        GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                        $record['amount'],
                        true,
                        self::STATUS_DESC_INSUFFICIENT_FUNDS,
                    );

                    $result[] = self::insufficientFundsTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                    continue;
                }

                // credit the amount to the player balance
                $locked_wallet->credit($record['amount']);

                $adjusted_bet_amount = $player_bet->valid_bet - $record['amount'];

                $player_bet->adjust($player_bet->bet_amount, $adjusted_bet_amount);

                $old_win_amount = $player_bet->win_amount ?? 0;

                $win_amount = $old_win_amount + $record['amount'];

                $bet_round = $player_bet->betRound;

                $win_loss = $win_amount - $player_bet->bet_amount;

                $player_bet->settle($win_amount, now()->toDateTimeString());

                $bet_round->close(
                    now()->toDateTimeString(),
                    $win_loss,
                    $player_bet->turnover,
                    $player_bet->valid_bet,
                    $win_loss,
                );

                $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

                $game_transaction_history->gameActionSuccess(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    $record['amount'],
                    false,
                    $locked_wallet->balance,
                    GameTransactionHistoryConstants::NOTE_SETTLE_INSURANCE_TRANSACTION,
                    null,
                    $player_bet->id,
                    $refer_transaction->id,
                );

                $player_balance_history->gameActionSuccess(
                    $record['amount'],
                    false,
                    $locked_wallet->balance,
                    $game_transaction_history->id,
                    GameTransactionHistoryConstants::NOTE_SETTLE_INSURANCE_TRANSACTION
                );

                $result[] = self::successTransactionalResponse($record['userId'], $record['ticketId'], $record['txnId'], $locked_wallet->balance);

                continue;
            }
        }

        // after the loop is done get the response array and pass it to the successResponse function
        return new UGSeamlessResponseDTO(self::successResponse($result), 200);
    }

    private static function getBalance($request_data, $game_item, UGCurrencyEnums $requested_currency): UGSeamlessResponseDTO
    {
        $user = User::where('user_name', $request_data['userId'])->first();

        if (!$user) {

            return new UGSeamlessResponseDTO(self::userNotFoundResponse(), 200);
        }

        $player = $user->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if ($player_game_currency !== $requested_currency) {
            return new UGSeamlessResponseDTO(self::currencyMismatchResponse(), 200);
        }

        return new UGSeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function checkLogin($request_data, $game_item, UGCurrencyEnums $requested_currency): UGSeamlessResponseDTO
    {
        $user = User::where('user_name', $request_data['userId'])->first();

        if (!$user) {

            return new UGSeamlessResponseDTO(self::userNotFoundResponse(), 200);
        }

        if (isset($request_data['token'])) {

            $credentials = self::getCredential($requested_currency);

            $tokens = $user->tokens;

            $first_token = $tokens->first();

            $token = hash('sha256', $request_data['userId'] . $first_token->token . $credentials['api_key']);

            if ($token !== $request_data['token']) {
                return new UGSeamlessResponseDTO(self::userTokenNotValidResponse(), 200);
            }
        }

        return new UGSeamlessResponseDTO(self::successResponse(true), 200);
    }

    private static function successTransactionalResponse($user_id, $ticket_id, $txnId, $balance)
    {
        return [
            'code' => self::STATUS_SUCCESS_RESPONSE,
            'msg' => self::STATUS_DESC_SUCCESS_RESPONSE,
            'userId' => $user_id,
            'ticketId' => $ticket_id,
            'balance' => $balance,
            'txnId' => $txnId,
        ];
    }

    private static function successResponse($data)
    {
        return [
            'code' => self::STATUS_SUCCESS_RESPONSE,
            'msg' => self::STATUS_DESC_SUCCESS_RESPONSE,
            'data' => $data,
        ];
    }

    private static function userNotFoundTransactionalResponse($user_id, $ticket_id, $txnId)
    {
        return [
            'code' => self::STATUS_USER_NOT_FOUND,
            'msg' => self::STATUS_DESC_USER_NOT_FOUND,
            'userId' => $user_id,
            'ticketId' => $ticket_id,
            'txnId' => $txnId,
        ];
    }

    private static function invalidTransactionTransactionalResponse($user_id, $ticket_id, $txnId, $balance)
    {
        return [
            'code' => self::STATUS_DESC_UNKNOWN_ERROR,
            'msg' => self::STATUS_DESC_TRANSACTION_DOES_NOT_EXIST,
            'userId' => $user_id,
            'ticketId' => $ticket_id,
            'balance' => self::roundBalance($balance),
            'txnId' => $txnId,
        ];
    }

    private static function invalidBetTransactionalResponse($user_id, $ticket_id, $txnId, $balance)
    {
        return [
            'code' => self::STATUS_DESC_UNKNOWN_ERROR,
            'msg' => self::STATUS_DESC_BET_DOES_NOT_EXIST,
            'userId' => $user_id,
            'ticketId' => $ticket_id,
            'balance' => self::roundBalance($balance),
            'txnId' => $txnId,
        ];
    }

    private static function changeTypeNotSupportedTransactionalResponse($user_id, $ticket_id, $txnId, $balance)
    {
        return [
            'code' => self::STATUS_UNKNOWN_ERROR,
            'msg' => self::STATUS_DESC_CHANGE_TYPE_NOT_SUPPORTED,
            'userId' => $user_id,
            'ticketId' => $ticket_id,
            'balance' => self::roundBalance($balance),
            'txnId' => $txnId,
        ];
    }

    private static function insufficientFundsTransactionalResponse($user_id, $ticket_id, $txnId, $balance)
    {
        return [
            'code' => self::STATUS_INSUFFICIENT_FUNDS,
            'msg' => self::STATUS_DESC_INSUFFICIENT_FUNDS,
            'userId' => $user_id,
            'ticketId' => $ticket_id,
            'balance' => self::roundBalance($balance),
            'txnId' => $txnId,
        ];
    }

    private static function currencyMismatchTransactionalResponse($user_id, $ticket_id, $txnId, $balance)
    {
        return [
            'code' => self::STATUS_CURRENCY_MISMATCH,
            'msg' => self::STATUS_DESC_CURRENCY_MISMATCH,
            'userId' => $user_id,
            'ticketId' => $ticket_id,
            'balance' => self::roundBalance($balance),
            'txnId' => $txnId,
        ];
    }

    private static function currencyMismatchResponse()
    {
        return [
            'code' => self::STATUS_CURRENCY_MISMATCH,
            'msg' => self::STATUS_DESC_CURRENCY_MISMATCH,
            'data' => false,
        ];
    }

    private static function userTokenNotValidResponse()
    {
        return [
            'code' => self::STATUS_SESSION_NOT_EXIST,
            'msg' => self::STATUS_DESC_SESSION_NOT_EXIST,
            'data' => false,
        ];
    }

    private static function userNotFoundResponse()
    {
        return [
            'code' => self::STATUS_USER_NOT_FOUND,
            'msg' => self::STATUS_DESC_USER_NOT_FOUND,
            'data' => false,
        ];
    }

    private static function actionNotSupportedResponse()
    {
        return [
            'code' => self::STATUS_ACTION_NOT_SUPPORTED,
            'msg' => self::STATUS_DESC_ACTION_NOT_SUPPORTED,
            'data' => false,
        ];
    }

    private static function gameItemNotFoundResponse()
    {
        return [
            'code' => self::STATUS_UNKNOWN_ERROR,
            'msg' => self::STATUS_DESC_GAME_ITEM_NOT_FOUND,
            'data' => false,
        ];
    }

    private static function unknownErrorResponse()
    {
        return [
            'code' => self::STATUS_UNKNOWN_ERROR,
            'msg' => self::STATUS_DESC_UNKNOWN_ERROR,
            'data' => false,
        ];
    }

    private static function authFailedResponse()
    {
        return [
            'code' => self::STATUS_AUTH_FAILED,
            'msg' => self::STATUS_DESC_AUTH_FAILED,
            'data' => false,
        ];
    }

    private static function validationErrorResponse($error)
    {
        return [
            'code' => self::STATUS_VALIDATION_ERROR,
            'msg' => self::STATUS_DESC_VALIDATION_ERROR,
            'error' => $error,
            'data' => false,
        ];
    }

    private static function ipNotAllowedResponse($ip)
    {
        return [
            'code' => self::STATUS_IP_NOT_ALLOWED,
            'msg' => self::STATUS_DESC_IP_NOT_ALLOWED,
            'ip' => $ip,
            'data' => false,
        ];
    }
}
