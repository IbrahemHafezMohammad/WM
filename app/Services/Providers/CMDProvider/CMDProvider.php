<?php

namespace App\Services\Providers\CMDProvider;

use Exception;
use DOMDocument;
use Carbon\Carbon;
use App\Models\Bet;
use App\Models\User;
use App\Models\Player;
use App\Models\BetRound;
use App\Models\GameItem;
use App\Models\GamePlatform;
use App\Constants\BetConstants;
use App\Constants\GlobalConstants;
use App\Jobs\CMDCheckTicketStatus;
use Illuminate\Support\Facades\Log;
use App\Models\PlayerBalanceHistory;
use Illuminate\Support\Facades\Http;
use App\Models\GameTransactionHistory;
use Illuminate\Support\Facades\Config;
use App\Constants\GamePlatformConstants;
use App\Services\Providers\ProviderInterface;
use App\Constants\GameTransactionHistoryConstants;
use App\Services\Providers\CMDProvider\Enums\CMDActionsEnums;
use App\Services\Providers\CMDProvider\Enums\CMDCurrencyEnums;
use App\Services\Providers\CMDProvider\Encryption\AesCbcEncryptor;
use App\Services\Providers\CMDProvider\DTOs\CMDSeamlessResponseDTO;

class CMDProvider implements ProviderInterface
{
    // get balance action ids
    const ACTION_ID_GET_BALANCE = 1000;

    // deduct balance (place bet) action ids
    const ACTION_ID_DEDUCT_BALANCE = 1003;

    // update balance action ids
    const ACTION_ID_DANGER_REFUND = 2001;
    const ACTION_ID_RESETTLE_TICKET = 2002;
    const ACTION_ID_BT_BUY_BACK = 3001;
    const ACTION_ID_SETTLE_HT = 4001;
    const ACTION_ID_SETTLE_FT = 4002;
    const ACTION_ID_SETTLE_PARLAY = 4003;
    const ACTION_ID_UNSETTLE_HT = 5001;
    const ACTION_ID_UNSETTLE_FT = 5002;
    const ACTION_ID_UNSETTLE_PARLAY = 5003;
    const ACTION_ID_CANCEL_HT = 6001;
    const ACTION_ID_CANCEL_FT = 6002;
    const ACTION_ID_UNCANCEL_HT = 7001;
    const ACTION_ID_UNCANCEL_FT = 7002;
    const ACTION_ID_SYSTEM_ADJUSTMENT = 9000;

    //response types
    const RESPONSE_TYPE_JSON = 'json';
    const RESPONSE_TYPE_XML = 'xml';

    // response statuses
    const STATUS_SUCCESS_RESPONSE = 100;
    const STATUS_AUTH_FAILED = 2;
    const STATUS_AUTH_SUCCESS = 0;
    const STATUS_FAILED_RESPONSE = -1;
    const STATUS_SERVER_EXCEPTION = -999;
    const STATUS_ACCESS_DENIED = -103;
    const STATUS_INVALID_ARGUMENTS = -110;
    const STATUS_USER_NOT_EXISTS = -97;

    // response descriptions
    const STATUS_DESC_SUCCESS = 'SUCCESS';
    const STATUS_DESC_FAILED = 'FAILED';
    const STATUS_DESC_UNKNOWN_ERROR = 'UNKNOWN_ERROR';
    const STATUS_DESC_AUTH_FAILED = 'AUTH_FAILED';
    const STATUS_DESC_VALIDATION_ERROR = 'VALIDATION_ERROR';
    const STATUS_DESC_IP_NOT_ALLOWED = 'IP_NOT_ALLOWED';
    const STATUS_DESC_CURRENCY_MISMATCH = 'CURRENCY_MISMATCH';
    const STATUS_DESC_INSUFFICIENT_FUNDS = 'INSUFFICIENT_FUNDS';
    const STATUS_DESC_INVALID_BET = 'INVALID_BET';
    const STATUS_DESC_GAME_ITEM_NOT_FOUND = 'GAME_ITEM_NOT_FOUND';
    const STATUS_DESC_SESSION_NOT_EXISTS = 'SESSION_NOT_EXISTS';
    const STATUS_DESC_USER_NOT_FOUND = 'USER_NOT_FOUND';
    const STATUS_DESC_DUPLICATED_REQUEST = 'DUPLICATED_REQUEST';
    const STATUS_DESC_ACTION_NOT_SUPPORTED = 'ACTION_NOT_SUPPORTED';

    // languages
    const LANG_EN = 'en-US';
    const LANG_VN = 'vi-VN';

    // templates
    const TEMPLATE_ALICEBLUE = 'alicblue';
    const TEMPLATE_BLUE = 'blue';
    const TEMPLATE_BLUE_GRAY = 'bluegray';
    const TEMPLATE_DARKER = 'darker';
    const TEMPLATE_GRAY = 'gray';
    const TEMPLATE_GREEN = 'green';

    protected $username;
    protected $currency;
    protected $pc_login_base_url;
    protected $mobile_login_base_url;
    protected $api_base_url;
    protected $partner_key;
    protected $auth_key;
    protected $headers;
    protected $language;
    protected $token;
    protected $template;

    public function __construct(Player $player, $game_code)
    {
        $this->username = $player->user->user_name;

        $this->currency = self::getGameCurrency($player->wallet->currency);
        $credentials = self::getCredential($this->currency);
        $this->pc_login_base_url = $credentials['pc_login_base_url'];
        $this->mobile_login_base_url = $credentials['mobile_login_base_url'];
        $this->api_base_url = $credentials['api_base_url'];
        $this->partner_key = $credentials['partner_key'];
        $this->auth_key = $credentials['auth_key'];

        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $this->language = self::getGameLanguage($player->language);
        $tokens = $player->user->tokens;
        $first_token = $tokens->first();
        $this->token = self::generateToken($this->username, $first_token->token);
        $this->template = self::TEMPLATE_ALICEBLUE;
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        try {

            $base_url = $this->pc_login_base_url;

            if ($deviceType == 'Mobile') {

                $base_url = $this->mobile_login_base_url;
            }

            $login_query = [
                'lang' => $this->language,
                'user' => $this->username,
                'currency' => $this->currency->value,
                'templatename' => $this->template,
                'token' => $this->token,
                // 'View' => $this->view,
            ];

            $queryString = http_build_query($login_query);

            $urlToSendToFrontend = $base_url . '/auth.aspx?' . $queryString;

            $data = [
                'Method' => 'exist',
                'PartnerKey' => $this->partner_key,
                'UserName' => $this->username,
            ];

            $response = Http::withHeaders($this->headers)->get($this->api_base_url, $data);
            
            $response = $response->json();

            if ($response['Data']) {
                $result = json_encode([
                    'is_user' => true,
                    'login_url' => $urlToSendToFrontend,
                ]);
            } else {
                $result = json_encode([
                    'is_user' => false,
                ]);
            }


            return $result;
        } catch (\Throwable $exception) {
            Log::info('***************************************************************************************');
            Log::info('CMD Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        try {

            $data = [
                'Method' => 'createmember',
                'PartnerKey' => $this->partner_key,
                'UserName' => $this->username,
                'Currency' => $this->currency->value,
            ];

            $response = Http::withHeaders($this->headers)->get($this->api_base_url, $data);

            // Log::info("UG REGISTER API RESULT");
            // Log::info(json_encode([
            //     'api_url' => $this->api_base_url,
            //     'request_headers' => $this->headers,
            //     'request_data' => $data,
            //     'response' => $response->body()
            // ]));

            return $response->body();
        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('CMD Provider Call registerToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function queryBetRecord(string $ref_no): ?string
    {
        try {

            $data = [
                'Method' => 'betrecordbyrefno',
                'PartnerKey' => $this->partner_key,
                'RefNo' => $ref_no,
            ];

            $response = Http::withHeaders($this->headers)->get($this->api_base_url, $data);

            Log::info("UG BET RECORD API RESULT");
            Log::info(json_encode([
                'api_url' => $this->api_base_url,
                'request_headers' => $this->headers,
                'request_data' => $data,
                'response' => $response->body()
            ]));

            return $response->body();
        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('CMD Provider Call queryBetRecord API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    private static function generateToken($username, $personalAccessToken)
    {
        // Generate MD5 hash of the personal access token
        $tokenHash = md5($personalAccessToken);

        // Concatenate the MD5 hash with the username
        $token = $tokenHash . $username;

        return $token;
    }

    private static function parseToken(string $tokenString)
    {
        // MD5 hash length is always 32 characters
        $hashLength = 32;

        // Extract the MD5 hash and username from the token
        $tokenHash = substr($tokenString, 0, $hashLength);
        $username = substr($tokenString, $hashLength);

        return [
            'md5' => $tokenHash,
            'username' => $username,
        ];
    }

    public static function convertTicksToUtcString($ticks): string
    {
        // One tick represents 100-nanoseconds or 1/10,000,000 of a second
        $ticksPerSecond = 10000000;

        // Convert ticks to Unix timestamp
        $unixTimestamp = ($ticks - GlobalConstants::EPOCH_TICKS) / $ticksPerSecond;

        // Create a Carbon instance from the Unix timestamp in UTC
        $utcDateTime = Carbon::createFromTimestampUTC($unixTimestamp);

        // Return the formatted date-time string
        return $utcDateTime->toDateTimeString(); // Default format is 'Y-m-d H:i:s'
    }

    private static function getCurrentTimeInTicks(): int
    {

        $ticksPerSecond = 10000000;

        $nowUnixTimestamp = Carbon::now('UTC')->timestamp;

        $currentTicks = (int) ($nowUnixTimestamp * $ticksPerSecond) + GlobalConstants::EPOCH_TICKS;

        return $currentTicks;
    }

    public static function getCredential(CMDCurrencyEnums $cmd_currency)
    {
        $partner_key = null;
        $auth_key = null;

        switch ($cmd_currency) {
            case CMDCurrencyEnums::VNDK:
                $partner_key = Config::get('app.cmd_partner_key.vndk');
                $auth_key = Config::get('app.cmd_auth_key.vndk');
                break;
            case CMDCurrencyEnums::PHP:
                $partner_key = Config::get('app.cmd_partner_key.php');
                $auth_key = Config::get('app.cmd_auth_key.php');
                break;
            case CMDCurrencyEnums::INR:
                $partner_key = Config::get('app.cmd_partner_key.inr');
                $auth_key = Config::get('app.cmd_auth_key.inr');
                break;
        }

        return [
            'partner_key' => $partner_key,
            'auth_key' => $auth_key,
            'pc_login_base_url' => Config::get('app.cmd_pc_login_base_url'),
            'mobile_login_base_url' => Config::get('app.cmd_mobile_login_base_url'),
            'api_base_url' => Config::get('app.cmd_api_base_url'),
        ];
    }

    public static function getBalanceActionIds()
    {
        return [
            self::ACTION_ID_GET_BALANCE,
        ];
    }

    public static function deductBalanceActionIds()
    {
        return [
            self::ACTION_ID_DEDUCT_BALANCE,
        ];
    }

    public static function updateBalanceActionIds()
    {
        return [
            self::ACTION_ID_DANGER_REFUND,
            self::ACTION_ID_RESETTLE_TICKET,
            self::ACTION_ID_BT_BUY_BACK,
            self::ACTION_ID_SETTLE_HT,
            self::ACTION_ID_SETTLE_FT,
            self::ACTION_ID_SETTLE_PARLAY,
            self::ACTION_ID_UNSETTLE_HT,
            self::ACTION_ID_UNSETTLE_FT,
            self::ACTION_ID_UNSETTLE_PARLAY,
            self::ACTION_ID_CANCEL_HT,
            self::ACTION_ID_CANCEL_FT,
            self::ACTION_ID_UNCANCEL_HT,
            self::ACTION_ID_UNCANCEL_FT,
            self::ACTION_ID_SYSTEM_ADJUSTMENT,
        ];
    }

    public static function getGameLanguage($language)
    {
        return match ($language) {
            GlobalConstants::LANG_EN => self::LANG_EN,
            GlobalConstants::LANG_VN => self::LANG_VN,
            default => self::LANG_EN,
        };
    }

    public static function getSystemCurrency(CMDCurrencyEnums $currency): int
    {
        return match ($currency) {
            CMDCurrencyEnums::VNDK => GlobalConstants::CURRENCY_VNDK,
            CMDCurrencyEnums::PHP => GlobalConstants::CURRENCY_PHP,
            CMDCurrencyEnums::INR => GlobalConstants::CURRENCY_INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getGameCurrency($currency): CMDCurrencyEnums
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => CMDCurrencyEnums::VNDK,
            GlobalConstants::CURRENCY_PHP => CMDCurrencyEnums::PHP,
            GlobalConstants::CURRENCY_INR => CMDCurrencyEnums::INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function roundBalance($balance)
    {
        return round($balance, 4);
    }

    public static function unknownError(): CMDSeamlessResponseDTO
    {
        return new CMDSeamlessResponseDTO(self::unknownErrorResponse(), 200);
    }

    public static function authFailed(): CMDSeamlessResponseDTO
    {
        return new CMDSeamlessResponseDTO(self::authFailedResponse(), 200);
    }

    public static function validationError($error): CMDSeamlessResponseDTO
    {
        return new CMDSeamlessResponseDTO(self::validationErrorResponse($error), 200);
    }

    public static function ipNotAllowed($ip): CMDSeamlessResponseDTO
    {
        return new CMDSeamlessResponseDTO(self::ipNotAllowedResponse($ip), 200);
    }

    public static function walletAccess($request_data, CMDActionsEnums $wallet_action, CMDCurrencyEnums $currency): CMDSeamlessResponseDTO
    {
        $game_item = GameItem::where('game_id', GamePlatformConstants::CMD_GAME_CODE_LOBBY)->first();

        if (!$game_item) {

            return new CMDSeamlessResponseDTO(self::gameItemNotFoundResponse(), 200);
        }

        return match ($wallet_action) {
            CMDActionsEnums::AUTH_CHECK => self::authCheck($request_data, $game_item, $currency),
            CMDActionsEnums::GET_BALANCE => self::getBalance($request_data, $game_item, $currency),
            CMDActionsEnums::DEDUCT_BALANCE => self::deductBalance($request_data, $game_item, $currency),
            CMDActionsEnums::UPDATE_BALANCE => self::updateBalance($request_data, $game_item, $currency),
            default => new CMDSeamlessResponseDTO(self::actionNotSupportedResponse('walletAccess'), 200),
        };
    }

    private static function updateBalance($request_data, $game_item, CMDCurrencyEnums $requested_currency): CMDSeamlessResponseDTO
    {
        $credential = self::getCredential($requested_currency);

        $dup_transaction = GameTransactionHistory::referenceNo($request_data['packageId'])->gamePlatformId($game_item->gamePlatform->id)->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

        if ($dup_transaction) {

            $response = self::duplicatedRequestResponse($request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

            return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
        }

        $records = $request_data['balancePackage']['TicketDetails'];

        $user_names = collect($records)->pluck('SourceName')->unique();

        $users = User::whereIn('user_name', $user_names)->with('player')->get()->keyBy('user_name');

        $references = collect($records)->pluck('ReferenceNo')->unique();

        $action_id = $request_data['balancePackage']['ActionId'];

        $bets = $game_item->bets()->referenceIn($references)->cmdActiveIdLogic($action_id)->get()->keyBy('bet_reference');

        foreach ($records as $record) {

            $user = $users->get($record['SourceName']);

            if (!$user) {

                $response = self::userNotFoundResponse($request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

                return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
            }

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $request_data['packageId'],
                null,
                $game_item->gamePlatform->id
            );

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if ($player_game_currency !== $requested_currency) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    0,
                    false,
                    self::STATUS_DESC_CURRENCY_MISMATCH,
                );

                $response = self::currencyMismatchResponse($request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

                return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
            }

            $player_bet = $bets->get($record['ReferenceNo']);

            if ($action_id !== self::ACTION_ID_SYSTEM_ADJUSTMENT && $action_id !== self::ACTION_ID_BT_BUY_BACK) {

                if (!$player_bet || $player_bet->betRound->player_id !== $player->id) {

                    $game_transaction_history->gameActionFailed(
                        GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                        0,
                        false,
                        self::STATUS_DESC_INVALID_BET,
                    );

                    $response = self::invalidBetResponse($request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

                    return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
                }
            }
        }

        return match ($action_id) {
            self::ACTION_ID_DANGER_REFUND,
            self::ACTION_ID_CANCEL_HT,
            self::ACTION_ID_CANCEL_FT => self::refund($request_data, $game_item, $credential, $records, $users, $bets),
            self::ACTION_ID_RESETTLE_TICKET => self::resettle($request_data, $game_item, $credential, $records, $users, $bets),
            self::ACTION_ID_BT_BUY_BACK => self::BTBuyBack($request_data, $game_item, $credential, $records, $users),
            self::ACTION_ID_SETTLE_FT,
            self::ACTION_ID_SETTLE_HT,
            self::ACTION_ID_SETTLE_PARLAY => self::settle($request_data, $game_item, $credential, $records, $users, $bets),
            self::ACTION_ID_UNSETTLE_HT,
            self::ACTION_ID_UNSETTLE_FT,
            self::ACTION_ID_UNSETTLE_PARLAY => self::unsettle($request_data, $game_item, $credential, $records, $users, $bets),
            self::ACTION_ID_UNCANCEL_HT,
            self::ACTION_ID_UNCANCEL_FT => self::uncancel($request_data, $game_item, $credential, $records, $users, $bets),
            self::ACTION_ID_SYSTEM_ADJUSTMENT => self::adjust($request_data, $game_item, $credential, $records, $users),
        };
    }

    private static function adjust($request_data, $game_item, $credential, $records, $users): CMDSeamlessResponseDTO
    {
        foreach ($records as $record) {

            $user = $users->get($record['SourceName']);

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            // init the game transaction history
            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $request_data['packageId'],
                null,
                $game_item->gamePlatform->id
            );

            // init the balance history
            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $is_withdraw = $record['TransactionAmount'] < 0 ? true : false;

            $amount = abs($record['TransactionAmount']);

            $is_withdraw ? $locked_wallet->debit($amount) : $locked_wallet->credit($amount);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ADJUST,
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION,
            );

            $player_balance_history->gameActionSuccess(
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION
            );
        }

        $response = self::successResponse(null, $request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

        return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
    }

    private static function unsettle($request_data, $game_item, $credential, $records, $users, $bets): CMDSeamlessResponseDTO
    {
        foreach ($records as $record) {

            $user = $users->get($record['SourceName']);

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_bet = $bets->get($record['ReferenceNo']);

            $player_bet->refresh();

            $bet_round = $player_bet->betRound;

            // init the game transaction history
            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $request_data['packageId'],
                null,
                $game_item->gamePlatform->id
            );

            // init the balance history
            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $amount = abs($record['TransactionAmount']);

            $locked_wallet->debit($amount);

            $player_bet->unsettle();

            $bet_round->reopen(null, $bet_round->total_turnovers, $bet_round->total_valid_bets, null);

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_UNSETTLE,
                $amount,
                true,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_TYPE_UNSETTLE_BET,
                null,
                $player_bet->id,
                $refer_transaction?->id,
            );

            $player_balance_history->gameActionSuccess(
                $amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_TYPE_UNSETTLE_BET
            );
        }

        $response = self::successResponse(null, $request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

        
        CMDCheckTicketStatus::dispatch(
            $player_bet->bet_reference,
            $game_item->id,
            $player->id
        )->delay(now()->addSeconds(1 * 60 * 60)); //1 * 60 * 60

        return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
    }

    private static function settle($request_data, $game_item, $credential, $records, $users, $bets): CMDSeamlessResponseDTO
    {
        foreach ($records as $record) {

            $user = $users->get($record['SourceName']);

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_bet = $bets->get($record['ReferenceNo']);

            $player_bet->refresh();

            $bet_round = $player_bet->betRound;

            // init the game transaction history
            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $request_data['packageId'],
                null,
                $game_item->gamePlatform->id
            );

            // init the balance history
            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $amount = $record['TransactionAmount'];

            $locked_wallet->credit($amount);

            $winloss = $amount - $player_bet->bet_amount;

            $timestamp = self::convertTicksToUtcString($request_data['dateSent']);

            if (isset($request_data['MatchID'])) {

                $player_bet->adjust(
                    $player_bet->bet_amount,
                    $player_bet->valid_bet,
                    $player_bet->turnover,
                    $player_bet->odds,
                    $player_bet->rebate,
                    $player_bet->comm,
                    $request_data['MatchID'],
                );
            }

            $player_bet->settle($amount, $timestamp);

            $bet_round->close($timestamp, $winloss, null, null, $amount);

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                $amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_HALF_SETTLE_TRANSACTION,
                null,
                $player_bet->id,
                $refer_transaction?->id,
            );

            $player_balance_history->gameActionSuccess(
                $amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_HALF_SETTLE_TRANSACTION
            );
        }

        $response = self::successResponse(null, $request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

        return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
    }

    private static function BTBuyBack($request_data, $game_item, $credential, $records, $users): CMDSeamlessResponseDTO
    {
        foreach ($records as $record) {

            $user = $users->get($record['SourceName']);

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            // init the game transaction history
            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $request_data['packageId'],
                null,
                $game_item->gamePlatform->id
            );

            // init the balance history
            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $cash_out_amount = $record['TransactionAmount'];

            $locked_wallet->credit($cash_out_amount);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                $cash_out_amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_CASH_OUT_TRANSACTION,
                $record['ReferenceNo']
            );

            $player_balance_history->gameActionSuccess(
                $cash_out_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_CASH_OUT_TRANSACTION
            );
        }

        $response = self::successResponse(null, $request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

        return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
    }

    private static function uncancel($request_data, $game_item, $credential, $records, $users, $bets): CMDSeamlessResponseDTO
    {
        foreach ($records as $record) {

            $user = $users->get($record['SourceName']);

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_bet = $bets->get($record['ReferenceNo']);

            $player_bet->refresh();

            $bet_round = $player_bet->betRound;

            // init the game transaction history
            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $request_data['packageId'],
                null,
                $game_item->gamePlatform->id
            );

            // init the balance history
            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $debit_amount = $record['TransactionAmount'];

            $locked_wallet->debit($debit_amount);

            $timestamp = self::convertTicksToUtcString($request_data['dateSent']);

            $player_bet->adjust(
                $player_bet->bet_amount,
                $player_bet->valid_bet,
                $player_bet->turnover,
                $player_bet->odds,
                $player_bet->rebate,
                $player_bet->comm,
                $request_data['MatchID'] ?? null,
            );

            $bet_round->reopen($bet_round->win_loss, $bet_round->total_turnovers, $bet_round->total_valid_bets, $bet_round->total_win_amount);

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ADJUST,
                $debit_amount,
                true,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $player_balance_history->gameActionSuccess(
                $debit_amount,
                true,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION
            );
        }

        $response = self::successResponse(null, $request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

        CMDCheckTicketStatus::dispatch(
            $player_bet->bet_reference,
            $game_item->id,
            $player->id
        )->delay(now()->addSeconds(1 * 60 * 60)); //1 * 60 * 60
        
        return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
    }

    private static function refund($request_data, $game_item, $credential, $records, $users, $bets): CMDSeamlessResponseDTO
    {
        foreach ($records as $record) {

            $user = $users->get($record['SourceName']);

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_bet = $bets->get($record['ReferenceNo']);

            $player_bet->refresh();

            $bet_round = $player_bet->betRound;

            // init the game transaction history
            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $request_data['packageId'],
                null,
                $game_item->gamePlatform->id
            );

            // init the balance history
            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refund_amount = $record['TransactionAmount'];

            $locked_wallet->credit($refund_amount);

            if (isset($request_data['MatchID'])) {

                $player_bet->adjust(
                    $player_bet->bet_amount,
                    $player_bet->valid_bet,
                    $player_bet->turnover,
                    $player_bet->odds,
                    $player_bet->rebate,
                    $player_bet->comm,
                    $request_data['MatchID'],
                );
            }

            $timestamp = self::convertTicksToUtcString($request_data['dateSent']);

            $player_bet->cancel($timestamp);

            $bet_round->close($timestamp, null);

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $refund_amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_REFUND_TRANSACTION,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $player_balance_history->gameActionSuccess(
                $refund_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_REFUND_TRANSACTION
            );
        }

        $response = self::successResponse(null, $request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

        return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
    }

    // this is to cancel and uncancel
    private static function resettle($request_data, $game_item, $credential, $records, $users, $bets): CMDSeamlessResponseDTO
    {
        foreach ($records as $record) {

            $user = $users->get($record['SourceName']);

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_bet = $bets->get($record['ReferenceNo']);

            $player_bet->refresh();

            $bet_round = $player_bet->betRound;

            // init the game transaction history
            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $request_data['packageId'],
                null,
                $game_item->gamePlatform->id
            );

            // init the balance history
            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $is_withdraw = $record['TransactionAmount'] < 0;

            $amount = abs($record['TransactionAmount']);

            $timestamp = self::convertTicksToUtcString($request_data['dateSent']);

            if ($is_withdraw) {

                $locked_wallet->debit($amount);

                $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT;

                // to make it unsettled
                $player_bet->adjust(
                    $player_bet->bet_amount,
                    $player_bet->valid_bet,
                    $player_bet->turnover,
                    $player_bet->odds,
                    $player_bet->rebate,
                    $player_bet->comm,
                    $request_data['MatchID'] ?? null,
                );

                $bet_round->reopen($bet_round->win_loss, $bet_round->total_turnovers, $bet_round->total_valid_bets, $bet_round->total_win_amount);
            } else {

                $locked_wallet->credit($amount);

                $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT;

                $player_bet->cancel($timestamp);

                $bet_round->close($timestamp, null);
            }

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $game_transaction_history->gameActionSuccess(
                $type,
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION,
                null,
                $player_bet->id,
                $refer_transaction?->id,
            );

            $player_balance_history->gameActionSuccess(
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION
            );
        }

        $response = self::successResponse(null, $request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

        return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
    }

    private static function deductBalance($request_data, $game_item, CMDCurrencyEnums $requested_currency): CMDSeamlessResponseDTO
    {
        $credential = self::getCredential($requested_currency);

        $user = User::where('user_name', $request_data['balancePackage']['SourceName'])->first();

        $dup_transaction = GameTransactionHistory::referenceNo($request_data['packageId'])->gamePlatformId($game_item->gamePlatform->id)->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

        if ($dup_transaction) {

            $response = self::duplicatedRequestResponse($request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

            return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
        }

        if (!$user) {

            $response = self::userNotFoundResponse($request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

            return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
        }

        $player = $user->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $deduction_amount = abs($request_data['balancePackage']['TransactionAmount']);

        // init the game transaction history
        $game_transaction_history = GameTransactionHistory::gameAction(
            $locked_wallet->balance,
            $player->id,
            $locked_wallet->currency,
            $locked_wallet->id,
            $game_item->id,
            $request_data['packageId'],
            null,
            $game_item->gamePlatform->id
        );

        // init the balance history
        $player_balance_history = PlayerBalanceHistory::gameAction(
            $player->id,
            $locked_wallet->balance,
            $locked_wallet->currency,
        );

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if ($player_game_currency !== $requested_currency) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $deduction_amount,
                true,
                self::STATUS_DESC_CURRENCY_MISMATCH,
            );

            $response = self::currencyMismatchResponse($request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

            return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
        }

        if ($locked_wallet->balance < $deduction_amount) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $deduction_amount,
                true,
                self::STATUS_DESC_INSUFFICIENT_FUNDS,
            );

            $response = self::insufficientFundsResponse($request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

            return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
        }

        $locked_wallet->debit($deduction_amount);

        $bet_round = BetRound::begin(
            $player->id,
            $game_item->gamePlatform->id,
            $request_data['balancePackage']['ReferenceNo'],
            self::convertTicksToUtcString($request_data['dateSent']),
            $locked_wallet->currency,
        );

        $player_bet = Bet::place(
            $deduction_amount,
            null,
            $request_data['balancePackage']['ReferenceNo'],
            $bet_round->id,
            $game_item->id,
            self::convertTicksToUtcString($request_data['dateSent']),
            $locked_wallet->currency,
        );

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
            $deduction_amount,
            true,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET,
            null,
            $player_bet->id,
        );

        $player_balance_history->gameActionSuccess(
            $deduction_amount,
            true,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET
        );

        $response = self::successResponse($locked_wallet->balance, $request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

        CMDCheckTicketStatus::dispatch(
                $request_data['balancePackage']['ReferenceNo'],
                $game_item->id,
                $player->id
            )->delay(now()->addSeconds(1 * 60 * 60)); //1 * 60 * 60

        return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
    }

    private static function getBalance($request_data, $game_item, CMDCurrencyEnums $requested_currency): CMDSeamlessResponseDTO
    {
        $credential = self::getCredential($requested_currency);

        $user = User::where('user_name', $request_data['balancePackage']['SourceName'])->first();

        if (!$user) {

            $response = self::userNotFoundResponse($request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

            return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
        }

        $player = $user->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if ($player_game_currency !== $requested_currency) {

            $response = self::currencyMismatchResponse($request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

            return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
        }

        $response = self::successResponse($locked_wallet->balance, $request_data['dateSent'], $request_data['packageId'], $credential['partner_key']);

        return new CMDSeamlessResponseDTO($response['encrypted'], 200, $response['plain']);
    }

    private static function authCheck($request_data, $game_item, CMDCurrencyEnums $requested_currency): CMDSeamlessResponseDTO
    {
        $credential = self::getCredential($requested_currency);

        $parsed_token = self::parseToken($request_data['token']);

        $user = User::where('user_name', $parsed_token['username'])->first();

        if (!$user) {

            return new CMDSeamlessResponseDTO(self::userNotFoundXMLResponse(), 200, [], self::RESPONSE_TYPE_XML);
        }

        $tokens = $user->tokens;

        $first_token = $tokens->first();

        $md5 = md5($first_token->token);

        if ($credential['auth_key'] !== $request_data['secret_key'] || $md5 !== $parsed_token['md5']) {
            return new CMDSeamlessResponseDTO(self::userTokenNotValidXMLResponse(), 200, [], self::RESPONSE_TYPE_XML);
        }

        return new CMDSeamlessResponseDTO(self::checkAuthSuccessXMLResponse($parsed_token['username']), 200, [], self::RESPONSE_TYPE_XML);
    }

    private static function checkAuthSuccessXMLResponse($username)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        $authenticate = $dom->createElement('authenticate');
        $dom->appendChild($authenticate);

        $memberId = $dom->createElement('member_id', $username);
        $authenticate->appendChild($memberId);

        $statusCode = $dom->createElement('status_code', self::STATUS_AUTH_SUCCESS);
        $authenticate->appendChild($statusCode);

        $message = $dom->createElement('message', self::STATUS_DESC_SUCCESS);
        $authenticate->appendChild($message);

        return $dom->saveXML();
    }

    private static function userNotFoundXMLResponse()
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        $authenticate = $dom->createElement('authenticate');
        $dom->appendChild($authenticate);

        $memberId = $dom->createElement('member_id');
        $authenticate->appendChild($memberId);

        $statusCode = $dom->createElement('status_code', self::STATUS_AUTH_FAILED);
        $authenticate->appendChild($statusCode);

        $message = $dom->createElement('message', self::STATUS_DESC_USER_NOT_FOUND);
        $authenticate->appendChild($message);

        return $dom->saveXML();
    }

    private static function userTokenNotValidXMLResponse()
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        $authenticate = $dom->createElement('authenticate');
        $dom->appendChild($authenticate);

        $memberId = $dom->createElement('member_id');
        $authenticate->appendChild($memberId);

        $statusCode = $dom->createElement('status_code', self::STATUS_AUTH_FAILED);
        $authenticate->appendChild($statusCode);
        $message = $dom->createElement('message', self::STATUS_DESC_SESSION_NOT_EXISTS);
        $authenticate->appendChild($message);

        return $dom->saveXML();
    }

    private static function successResponse($balance, $data_received, $package_id, $partner_key)
    {
        $response = [
            'StatusCode' => self::STATUS_SUCCESS_RESPONSE,
            'StatusMessage' => self::STATUS_DESC_SUCCESS,
            'PackageId' => $package_id,
            'DateReceived' => (int) $data_received,
            'DateSent' => self::getCurrentTimeInTicks(),
        ];

        if (!is_null($balance)) {

            $response['Balance'] = $balance;
        }

        $plain_text = json_encode($response);

        $aes_encryptor = new AesCbcEncryptor($partner_key);

        $encrypted = $aes_encryptor->encrypt($plain_text);

        return [
            'encrypted' => $encrypted,
            'plain' => ['response_before_encryption' => $response],
        ];
    }

    private static function duplicatedRequestResponse($data_received, $package_id, $partner_key)
    {
        $response = [
            'StatusCode' => self::STATUS_FAILED_RESPONSE,
            'StatusMessage' => self::STATUS_DESC_DUPLICATED_REQUEST,
            'PackageId' => $package_id,
            'DateReceived' => (int) $data_received,
            'DateSent' => self::getCurrentTimeInTicks(),
        ];

        $plain_text = json_encode($response);

        $aes_encryptor = new AesCbcEncryptor($partner_key);

        $encrypted = $aes_encryptor->encrypt($plain_text);

        return [
            'encrypted' => $encrypted,
            'plain' => ['response_before_encryption' => $response],
        ];
    }

    private static function userNotFoundResponse($data_received, $package_id, $partner_key)
    {
        $response = [
            'StatusCode' => self::STATUS_USER_NOT_EXISTS,
            'StatusMessage' => self::STATUS_DESC_USER_NOT_FOUND,
            'PackageId' => $package_id,
            'DateReceived' => (int) $data_received,
            'DateSent' => self::getCurrentTimeInTicks(),
        ];

        $plain_text = json_encode($response);

        $aes_encryptor = new AesCbcEncryptor($partner_key);

        $encrypted = $aes_encryptor->encrypt($plain_text);

        return [
            'encrypted' => $encrypted,
            'plain' => ['response_before_encryption' => $response],
        ];
    }

    private static function insufficientFundsResponse($data_received, $package_id, $partner_key)
    {
        $response = [
            'StatusCode' => self::STATUS_FAILED_RESPONSE,
            'StatusMessage' => self::STATUS_DESC_INSUFFICIENT_FUNDS,
            'PackageId' => $package_id,
            'DateReceived' => (int) $data_received,
            'DateSent' => self::getCurrentTimeInTicks(),
        ];

        $plain_text = json_encode($response);

        $aes_encryptor = new AesCbcEncryptor($partner_key);

        $encrypted = $aes_encryptor->encrypt($plain_text);

        return [
            'encrypted' => $encrypted,
            'plain' => ['response_before_encryption' => $response],
        ];
    }

    private static function invalidBetResponse($data_received, $package_id, $partner_key)
    {
        $response = [
            'StatusCode' => self::STATUS_FAILED_RESPONSE,
            'StatusMessage' => self::STATUS_DESC_INVALID_BET,
            'PackageId' => $package_id,
            'DateReceived' => (int) $data_received,
            'DateSent' => self::getCurrentTimeInTicks(),
        ];

        $plain_text = json_encode($response);

        $aes_encryptor = new AesCbcEncryptor($partner_key);

        $encrypted = $aes_encryptor->encrypt($plain_text);

        return [
            'encrypted' => $encrypted,
            'plain' => ['response_before_encryption' => $response],
        ];
    }

    private static function currencyMismatchResponse($data_received, $package_id, $partner_key)
    {
        $response = [
            'StatusCode' => self::STATUS_FAILED_RESPONSE,
            'StatusMessage' => self::STATUS_DESC_CURRENCY_MISMATCH,
            'PackageId' => $package_id,
            'DateReceived' => (int) $data_received,
            'DateSent' => self::getCurrentTimeInTicks(),
        ];

        $plain_text = json_encode($response);

        $aes_encryptor = new AesCbcEncryptor($partner_key);

        $encrypted = $aes_encryptor->encrypt($plain_text);

        return [
            'encrypted' => $encrypted,
            'plain' => ['response_before_encryption' => $response],
        ];
    }

    private static function actionNotSupportedResponse($m = null)
    {
        return [
            'StatusCode' => self::STATUS_FAILED_RESPONSE,
            'StatusMessage' => self::STATUS_DESC_ACTION_NOT_SUPPORTED,
            'message' => $m,
        ];
    }

    private static function gameItemNotFoundResponse()
    {
        return [
            'StatusCode' => self::STATUS_FAILED_RESPONSE,
            'StatusMessage' => self::STATUS_DESC_GAME_ITEM_NOT_FOUND,
        ];
    }

    private static function unknownErrorResponse()
    {
        return [
            'StatusCode' => self::STATUS_SERVER_EXCEPTION,
            'StatusMessage' => self::STATUS_DESC_UNKNOWN_ERROR,
        ];
    }

    private static function authFailedResponse()
    {
        return [
            'StatusCode' => self::STATUS_FAILED_RESPONSE,
            'StatusMessage' => self::STATUS_DESC_AUTH_FAILED,
        ];
    }

    private static function validationErrorResponse($error)
    {
        return [
            'StatusCode' => self::STATUS_INVALID_ARGUMENTS,
            'StatusMessage' => self::STATUS_DESC_VALIDATION_ERROR,
            'error' => $error,
        ];
    }

    private static function ipNotAllowedResponse($ip)
    {
        return [
            'StatusCode' => self::STATUS_ACCESS_DENIED,
            'StatusMessage' => self::STATUS_DESC_IP_NOT_ALLOWED,
            'ip' => $ip,
        ];
    }
}
