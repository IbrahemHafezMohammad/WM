<?php

namespace App\Services\Providers\SSProvider;

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
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Log;
use App\Models\PlayerBalanceHistory;
use Illuminate\Support\Facades\Http;
use App\Models\GameTransactionHistory;
use Illuminate\Support\Facades\Config;
use App\Constants\GamePlatformConstants;
use App\Services\Providers\ProviderInterface;
use App\Constants\GameTransactionHistoryConstants;
use App\Services\Providers\SSProvider\Enums\SSActionsEnums;
use App\Services\Providers\SSProvider\Enums\SSCurrencyEnums;
use App\Services\Providers\SSProvider\DTOs\SSSeamlessResponseDTO;

class SSProvider implements ProviderInterface
{
    // results 
    const RESULT_SUCCESS = 1;
    const RESULT_FAILED = 0;

    // error codes
    const ERROR_CODE_INVALID_IP = 'ERR-1b';
    const ERROR_CODE_INVALID_PARAM = 'ERR-1c';
    const ERROR_CODE_INVALID_PASS_KEY = 'ERR-1i';
    const ERROR_CODE_INVALID_MEMBER_CURRENCY = 'ERR-26d';
    const ERROR_CODE_INVALID_USER_NAME = 'ERR-3b';
    const ERROR_CODE_INVALID_WAGER_ID = 'ERR-15b';
    const ERROR_CODE_INVALID_INSUFFICIENT_FUNDS = 'EC-06';
    const ERROR_CODE_INVALID_GAME_ITEM = 'GAME_ITEM_NOT_FOUND';
    const ERROR_CODE_INVALID_TRACTION_COUNT = 'TRACTION_COUNT';
    const ERROR_CODE_INVALID_DUPLICATED_REQUEST = 'DUPLICATED_REQUEST';
    const ERROR_CODE_UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    // error statuses
    const ERROR_STATUS_INVALID_IP = 2;
    const ERROR_STATUS_INVALID_PARAM = 3;
    const ERROR_STATUS_INVALID_PASS_KEY = 9;
    const ERROR_STATUS_INVALID_MEMBER_CURRENCY = 13;
    const ERROR_STATUS_INVALID_USER_NAME = 11;
    const ERROR_STATUS_INVALID_WAGER_ID = 11;
    const ERROR_STATUS_INVALID_INSUFFICIENT_FUNDS = 403;
    const ERROR_STATUS_INVALID_GAME_ITEM = 403;
    const ERROR_STATUS_INVALID_TRACTION_COUNT = 403;
    const ERROR_STATUS_INVALID_DUPLICATED_REQUEST = 403;
    const ERROR_STATUS_UNKNOWN_ERROR = 500;

    // error desc
    const ERROR_DESC_INVALID_IP = 'Invalid IP';
    const ERROR_DESC_INVALID_PARAM = 'Invalid param';
    const ERROR_DESC_INVALID_PASS_KEY = 'Invalid passkey';
    const ERROR_DESC_INVALID_MEMBER_CURRENCY = 'Invalid Member Currency';
    const ERROR_DESC_INVALID_USER_NAME = 'Invalid username';
    const ERROR_DESC_INVALID_WAGER_ID = 'Invalid wager id';
    const ERROR_DESC_INVALID_INSUFFICIENT_FUNDS = 'Insufficient Fund';
    const ERROR_DESC_INVALID_GAME_ITEM = 'Invalid Game Item';
    const ERROR_DESC_INVALID_ACTION = 'Invalid Action';
    const ERROR_DESC_INVALID_TRACTION_COUNT = 'Transaction Count More Than 1';
    const ERROR_DESC_INVALID_DUPLICATED_REQUEST = 'Duplicated Request';
    const ERROR_DESC_UNKNOWN_ERROR = 'Unknown Error';

    // odds types 
    const ODDS_TYPE_HONG_KONG = '1';
    const ODDS_TYPE_MALAY = '2';
    const ODDS_TYPE_INDO = '3';

    // interfaces 
    const INTERFACE_INTERNATIONAL = 'itr';

    //languages
    const LANG_EN = '1';
    const LANG_VN = '6';

    protected $username;

    protected $vendor_member_id;

    protected $currency;

    protected $base_url;

    protected $external_url;

    protected $pass_key;

    protected $company_code;

    protected $prefix_code;

    protected $headers;

    protected $language;

    function __construct(Player $player, $game_code)
    {
        $this->vendor_member_id = md5($player->user->user_name);

        $this->currency = self::getGameCurrency($player->wallet->currency);
        $credentials = self::getCredential($this->currency);
        $this->company_code = $credentials['company_code'];
        $this->prefix_code = $credentials['prefix_code'];
        $this->pass_key = $credentials['pass_key'];
        $this->base_url = $credentials['base_url'];
        $this->external_url = $credentials['external_url'];

        $this->username = $this->prefix_code . $player->user->user_name;

        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Encoding' => 'gzip',
            'passkey' => $this->pass_key,
            'prefix' => $this->prefix_code,
            'langs' => self::LANG_EN,
        ];

        $this->language = self::getGameLanguage($player->language);
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        try {

            $device = 2; // desktop

            if ($deviceType == 'Mobile') {

                $device = 1; // mobile
            }

            $data = [
                'username' => $this->username,
                'platform' => $device,
                'interface' => self::INTERFACE_INTERNATIONAL,
                'lobby' => base64_encode($this->external_url),
            ];

            $response = Http::withHeaders($this->headers)->get($this->base_url . '/GetUrl', $data);

            $result =  $response->json();

            if (isset($result['Data'])) {

                Log::info("IN THE SS URL CHECK");
                $result['Data'] = getUrl($result['Data'], [
                    'langs' => $this->language,
                    // 'ot' => self::ODDS_TYPE_HONG_KONG,
                ]);
            }

            // Log::info("SS GAME RESULT");
            // Log::info(json_encode([
            //     'api_url' => $this->base_url . '/GetUrl',
            //     'request_headers' => $this->headers,
            //     'request_data' => $data,
            //     'response' => $response->body()
            // ]));

            return json_encode($result);
            // return json_encode([
            //     'api_url' => $this->base_url . '/GetUrl',
            //     'request_headers' => $this->headers,
            //     'request_data' => $data,
            //     'response' => $response->body()
            // ]);

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('SS Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        try {

            $data = [
                'username' => $this->username,
                'currency' => $this->currency->value,
                'vendor_member_id' => $this->vendor_member_id,
            ];

            $response = Http::withHeaders($this->headers)->get($this->base_url . '/CreateMember', $data);

            return $response->body();
            // return json_encode([
            //     'api_url' => $this->base_url . '/CreateMember',
            //     'request_headers' => $this->headers,
            //     'request_data' => $data,
            //     'response' => $response->body()
            // ]);

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('SS Provider Call registerToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public static function getCredential(SSCurrencyEnums $ss_currency)
    {
        $company_code = null;
        $prefix_code = null;
        $pass_key = null;
        $external_url = null;

        switch ($ss_currency) {
            case SSCurrencyEnums::VNDK:
                $company_code = Config::get('app.ss_company_code.vndk');
                $prefix_code = Config::get('app.ss_prefix_code.vndk');
                $pass_key = Config::get('app.ss_pass_key.vndk');
                $external_url = Config::get('app.ss_external_url.vndk');
                break;
            case SSCurrencyEnums::PHP:
                $company_code = Config::get('app.ss_company_code.php');
                $prefix_code = Config::get('app.ss_prefix_code.php');
                $pass_key = Config::get('app.ss_pass_key.php');
                $external_url = Config::get('app.ss_external_url.php');
                break;
            case SSCurrencyEnums::INR:
                $company_code = Config::get('app.ss_company_code.inr');
                $prefix_code = Config::get('app.ss_prefix_code.inr');
                $pass_key = Config::get('app.ss_pass_key.inr');
                $external_url = Config::get('app.ss_external_url.inr');
                break;
        }

        return [
            'base_url' => Config::get('app.ss_base_url'),
            'company_code' => $company_code,
            'prefix_code' => $prefix_code,
            'pass_key' => $pass_key,
            'external_url' => $external_url,
        ];
    }

    public static function getSystemCurrency(SSCurrencyEnums $currency): int
    {
        return match ($currency) {
            SSCurrencyEnums::VNDK => GlobalConstants::CURRENCY_VNDK,
            SSCurrencyEnums::PHP => GlobalConstants::CURRENCY_PHP,
            SSCurrencyEnums::INR => GlobalConstants::CURRENCY_INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getGameCurrency($currency): SSCurrencyEnums
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => SSCurrencyEnums::VNDK,
            GlobalConstants::CURRENCY_PHP => SSCurrencyEnums::PHP,
            GlobalConstants::CURRENCY_INR => SSCurrencyEnums::INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getGameLanguage($language)
    {
        return match ($language) {
            GlobalConstants::LANG_EN => self::LANG_EN,
            GlobalConstants::LANG_VN => self::LANG_VN,
            default => self::LANG_EN,
        };
    }

    public static function roundBalance($balance)
    {
        return round($balance, 4);
    }

    public static function parseUsername($prefixed_username, $currency)
    {
        $credentials = self::getCredential($currency);

        $prefix = $credentials['prefix_code'];

        if (strpos($prefixed_username, $prefix) === 0) {

            $real_username = substr($prefixed_username, strlen($prefix));
        } else {

            $real_username = $prefixed_username;
        }

        return $real_username;
    }

    public static function authorizeProvider($pass_key, SSCurrencyEnums $ss_currency)
    {
        $credentials = self::getCredential($ss_currency);

        return true;
        // return $pass_key === $credentials['pass_key'];
    }

    public static function unknownError(): SSSeamlessResponseDTO
    {
        return new SSSeamlessResponseDTO(self::unknownErrorResponse(), 200);
    }

    public static function authFailed(): SSSeamlessResponseDTO
    {
        return new SSSeamlessResponseDTO(self::authFailedResponse(), 200);
    }

    public static function validationError($error): SSSeamlessResponseDTO
    {
        return new SSSeamlessResponseDTO(self::validationErrorResponse($error), 200);
    }

    public static function ipNotAllowed($ip): SSSeamlessResponseDTO
    {
        return new SSSeamlessResponseDTO(self::ipNotAllowedResponse($ip), 200);
    }

    public static function walletAccess($request_data, $wallet_action, $requested_currency): SSSeamlessResponseDTO
    {
        $game_item = GameItem::where('game_id', GamePlatformConstants::SS_GAME_CODE_LOBBY)->first();

        if (!$game_item) {

            return new SSSeamlessResponseDTO(self::gameItemNotFoundResponse(), 200);
        }

        return match ($wallet_action) {
            SSActionsEnums::PING => new SSSeamlessResponseDTO(self::pingResponse(), 200, [], false),
            SSActionsEnums::GET_BALANCE => self::getBalance($request_data, $game_item, $requested_currency),
            SSActionsEnums::DEDUCT_BALANCE => self::deductBalance($request_data, $game_item, $requested_currency),
            SSActionsEnums::ROLLBACK_TRANSACTION => self::rollBack($request_data, $game_item, $requested_currency),
            SSActionsEnums::CHECK_TRANSACTION => self::checkTransaction($request_data, $game_item, $requested_currency),
                // SSActionsEnums::SETTLE => self::updateBalance($request_data, $game_item, $requested_currency),
                // SSActionsEnums::TRACKER => self::updateBalance($request_data, $game_item, $requested_currency),
                // SSActionsEnums::PROMOTION => self::updateBalance($request_data, $game_item, $requested_currency),
            default => new SSSeamlessResponseDTO(self::actionNotSupportedResponse(), 200),
        };
    }

    private static function checkTransaction($request_data, $game_item, $requested_currency): SSSeamlessResponseDTO
    {
        
    }

    private static function rollBack($request_data, $game_item, $requested_currency): SSSeamlessResponseDTO
    {
        if (count($request_data['trxresult']) > 1) {

            return new SSSeamlessResponseDTO(self::transactionCountResponse(), 200);
        }

        $record = $request_data['trxresult'][0];

        $player_bet = $game_item->bets()
            ->status(BetConstants::STATUS_UNSETTLED)
            ->reference($record['refer_transid'])
            ->first();

        if (!$player_bet) {

            return new SSSeamlessResponseDTO(
                self::successResponse(
                    $record['refer_transid'],
                    rolltrx: $record['transid']
                ),
                200
            );
        }

        $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

        $player = $player_bet->betRound->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $game_transaction_history = GameTransactionHistory::gameAction(
            $locked_wallet->balance,
            $player->id,
            $locked_wallet->currency,
            $locked_wallet->id,
            $game_item->id,
            $record['transid'],
            null,
            $game_item->gamePlatform->id
        );

        $player_balance_history = PlayerBalanceHistory::gameAction(
            $player->id,
            $locked_wallet->balance,
            $locked_wallet->currency,
        );

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if ($player_game_currency !== $requested_currency) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $refer_transaction->points,
                false,
                self::ERROR_DESC_INVALID_MEMBER_CURRENCY,
            );

            return new SSSeamlessResponseDTO(self::currencyMismatchResponse(
                $record['refer_transid'],
                $locked_wallet->balance,
                $locked_wallet->balance,
                $refer_transaction->points,
                $record['transid']
            ), 200);
        }

        $before_balance = $locked_wallet->balance;

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

        return new SSSeamlessResponseDTO(
            self::successResponse(
                $record['refer_transid'],
                $before_balance,
                $locked_wallet->balance,
                $refer_transaction->points,
                $record['transid'],
            ),
            200
        );
    }

    private static function deductBalance($request_data, $game_item, $requested_currency): SSSeamlessResponseDTO
    {
        $username = self::parseUsername($request_data['acc'], $requested_currency);

        $user = User::where('user_name', $username)->first();

        if (!$user) {

            return new SSSeamlessResponseDTO(self::userNotFoundResponse(), 200);
        }

        $player = $user->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $dup_transaction = GameTransactionHistory::referenceNo($request_data['transid'])
            ->gamePlatformId($game_item->gamePlatform->id)
            ->status(GameTransactionHistoryConstants::STATUS_SUCCESS)
            ->playerId($player->id)
            ->first();

        if ($dup_transaction) {

            return new SSSeamlessResponseDTO(self::duplicatedRequestResponse(), 200);
        }

        $deduction_amount = abs($request_data['amt']);

        // init the game transaction history
        $game_transaction_history = GameTransactionHistory::gameAction(
            $locked_wallet->balance,
            $player->id,
            $locked_wallet->currency,
            $locked_wallet->id,
            $game_item->id,
            $request_data['transid'],
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
                self::ERROR_DESC_INVALID_MEMBER_CURRENCY,
            );

            return new SSSeamlessResponseDTO(self::currencyMismatchResponse($request_data['transid'], $locked_wallet->balance, $locked_wallet->balance, $request_data['amt']), 200);
        }

        if ($locked_wallet->balance < $deduction_amount) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $deduction_amount,
                true,
                self::ERROR_DESC_INVALID_DUPLICATED_REQUEST,
            );

            return new SSSeamlessResponseDTO(self::insufficientFundsResponse($request_data['transid'], $locked_wallet->balance, $locked_wallet->balance, $request_data['amt']), 200);
        }

        $before_balance = $locked_wallet->balance;

        $locked_wallet->debit($deduction_amount);

        $bet_round = BetRound::begin(
            $player->id,
            $game_item->gamePlatform->id,
            $request_data['transid'],
            now()->toDateTimeString(),
            $locked_wallet->currency,
        );

        $player_bet = Bet::place(
            $deduction_amount,
            null,
            $request_data['transid'],
            $bet_round->id,
            $game_item->id,
            now()->toDateTimeString(),
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

        return new SSSeamlessResponseDTO(self::successResponse($request_data['transid'], $before_balance, $locked_wallet->balance, $request_data['amt']), 200);
    }

    private static function getBalance($request_data, $game_item, $requested_currency): SSSeamlessResponseDTO
    {
        $username = self::parseUsername($request_data['acc'], $requested_currency);

        $user = User::where('user_name', $username)->first();

        if (!$user) {

            return new SSSeamlessResponseDTO(self::userNotFoundResponse(), 200);
        }

        $player = $user->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if ($player_game_currency !== $requested_currency) {
            return new SSSeamlessResponseDTO(self::currencyMismatchResponse(), 200);
        }

        return new SSSeamlessResponseDTO(self::successBalanceResponse($locked_wallet->balance), 200);
    }

    private static function successResponse(
        $transid = null,
        $before_balance = null,
        $after_balance = null,
        $amount = null,
        $rolltrx = null,
    ) {
        return [
            'recv' => self::RESULT_SUCCESS,
            'trx' => $transid,
            'transid' => $transid,
            'result' => self::RESULT_SUCCESS,
            'error_code' => "",
            'dtime' => now('GMT-4')->toDateTimeString(),
            'balancebefore' => $before_balance,
            'balanceafter' => $after_balance,
            'transamt' => $amount,
            'rolltrx' => $rolltrx,
        ];
    }

    private static function successBalanceResponse($balance)
    {
        return [
            'result' => self::RESULT_SUCCESS,
            'error_code' => "",
            'balance' => self::roundBalance($balance),
            'dtime' => now('GMT-4')->toDateTimeString(),
            'maintenance_flag' => 0,
        ];
    }


    private static function pingResponse()
    {
        return [
            'maintenance_flag' => 0,
            'out_going_ip' => Config::get('app.out_going_ip'),
            'ping_status' => 1,
            'ping_time' => round(microtime(true) - LARAVEL_START),
            'ping_remark' => "",
            'dtime' => now('GMT-4')->toDateTimeString(),
            'maintenance_flag' => 0,
        ];
    }

    private static function duplicatedRequestResponse($transid = null, $before_balance = null, $after_balance = null, $amount = null)
    {
        return [
            'transid' => $transid,
            'result' => self::RESULT_FAILED,
            'error_status' => self::ERROR_STATUS_INVALID_DUPLICATED_REQUEST,
            'error_code' => self::ERROR_CODE_INVALID_DUPLICATED_REQUEST,
            'remark' => self::ERROR_DESC_INVALID_DUPLICATED_REQUEST,
            'dtime' => now('GMT-4')->toDateTimeString(),
            'balancebefore' => $before_balance,
            'balanceafter' => $after_balance,
            'transamt' => $amount,
            'maintenance_flag' => 0,
        ];
    }

    private static function insufficientFundsResponse($transid = null, $before_balance = null, $after_balance = null, $amount = null)
    {
        return [
            'transid' => $transid,
            'result' => self::RESULT_FAILED,
            'error_status' => self::ERROR_STATUS_INVALID_INSUFFICIENT_FUNDS,
            'error_code' => self::ERROR_CODE_INVALID_INSUFFICIENT_FUNDS,
            'remark' => self::ERROR_DESC_INVALID_INSUFFICIENT_FUNDS,
            'dtime' => now('GMT-4')->toDateTimeString(),
            'balancebefore' => $before_balance,
            'balanceafter' => $after_balance,
            'transamt' => $amount,
            'maintenance_flag' => 0,
        ];
    }

    private static function transactionCountResponse()
    {
        return [
            'result' => self::RESULT_FAILED,
            'error_status' => self::ERROR_STATUS_INVALID_TRACTION_COUNT,
            'error_code' => self::ERROR_CODE_INVALID_TRACTION_COUNT,
            'remark' => self::ERROR_DESC_INVALID_TRACTION_COUNT,
            'dtime' => now('GMT-4')->toDateTimeString(),
            'maintenance_flag' => 0,
        ];
    }

    private static function currencyMismatchResponse($transid = null, $before_balance = null, $after_balance = null, $amount = null, $rolltrx = null)
    {
        return [
            'recv' => self::RESULT_FAILED,
            'trx' => $transid,
            'transid' => $transid,
            'result' => self::RESULT_FAILED,
            'error_status' => self::ERROR_STATUS_INVALID_MEMBER_CURRENCY,
            'error_code' => self::ERROR_CODE_INVALID_MEMBER_CURRENCY,
            'remark' => self::ERROR_DESC_INVALID_MEMBER_CURRENCY,
            'dtime' => now('GMT-4')->toDateTimeString(),
            'balancebefore' => $before_balance,
            'balanceafter' => $after_balance,
            'transamt' => $amount,
            'maintenance_flag' => 0,
            'rolltrx' => $rolltrx,
        ];
    }

    private static function userNotFoundResponse($transid = null, $before_balance = null, $after_balance = null, $amount = null)
    {
        return [
            'transid' => $transid,
            'result' => self::RESULT_FAILED,
            'error_status' => self::ERROR_STATUS_INVALID_USER_NAME,
            'error_code' => self::ERROR_CODE_INVALID_USER_NAME,
            'remark' => self::ERROR_DESC_INVALID_USER_NAME,
            'dtime' => now('GMT-4')->toDateTimeString(),
            'balancebefore' => $before_balance,
            'balanceafter' => $after_balance,
            'transamt' => $amount,
            'maintenance_flag' => 0,
        ];
    }

    private static function actionNotSupportedResponse()
    {
        return [
            'result' => self::RESULT_FAILED,
            'remark' => self::ERROR_DESC_INVALID_ACTION,
            'dtime' => now('GMT-4')->toDateTimeString(),
            'maintenance_flag' => 0,
        ];
    }

    private static function gameItemNotFoundResponse()
    {
        return [
            'result' => self::RESULT_FAILED,
            'error_status' => self::ERROR_STATUS_INVALID_GAME_ITEM,
            'error_code' => self::ERROR_CODE_INVALID_GAME_ITEM,
            'remark' => self::ERROR_DESC_INVALID_GAME_ITEM,
            'dtime' => now('GMT-4')->toDateTimeString(),
            'maintenance_flag' => 0,
        ];
    }

    private static function unknownErrorResponse()
    {
        return [
            'result' => self::RESULT_FAILED,
            'error_status' => self::ERROR_STATUS_UNKNOWN_ERROR,
            'error_code' => self::ERROR_CODE_UNKNOWN_ERROR,
            'remark' => self::ERROR_DESC_UNKNOWN_ERROR,
            'dtime' => now('GMT-4')->toDateTimeString(),
            'maintenance_flag' => 0,
        ];
    }

    private static function authFailedResponse()
    {
        return [
            'result' => self::RESULT_FAILED,
            'error_status' => self::ERROR_STATUS_INVALID_PASS_KEY,
            'error_code' => self::ERROR_CODE_INVALID_PASS_KEY,
            'remark' => self::ERROR_DESC_INVALID_PASS_KEY,
            'dtime' => now('GMT-4')->toDateTimeString(),
            'maintenance_flag' => 0,
        ];
    }

    private static function validationErrorResponse($error)
    {
        return [
            'result' => self::RESULT_FAILED,
            'error_status' => self::ERROR_STATUS_INVALID_PARAM,
            'error_code' => self::ERROR_CODE_INVALID_PARAM,
            'remark' => self::ERROR_DESC_INVALID_PARAM,
            'error' => $error,
            'dtime' => now('GMT-4')->toDateTimeString(),
            'maintenance_flag' => 0,
        ];
    }

    private static function ipNotAllowedResponse($ip)
    {
        return [
            'result' => self::RESULT_FAILED,
            'error_status' => self::ERROR_STATUS_INVALID_IP,
            'error_code' => self::ERROR_CODE_INVALID_IP,
            'remark' => self::ERROR_DESC_INVALID_IP,
            'ip' => $ip,
            'dtime' => now('GMT-4')->toDateTimeString(),
            'maintenance_flag' => 0,
        ];
    }
}
