<?php

namespace App\Services\Providers\DS88Provider;

use App\Constants\BetConstants;
use App\Constants\GamePlatformConstants;
use App\Constants\GameTransactionHistoryConstants;
use App\Constants\GlobalConstants;
use App\Models\Bet;
use App\Models\BetRound;
use App\Models\GameItem;
use App\Models\GamePlatform;
use App\Models\GameTransactionHistory;
use App\Models\Player;
use App\Models\PlayerBalanceHistory;
use App\Models\User;
use App\Services\Providers\DS88Provider\DTOs\DS88SeamlessResponseDTO;
use App\Services\Providers\DS88Provider\Enums\DS88ActionsEnums;
use App\Services\Providers\DS88Provider\Enums\DS88CurrencyEnums;
use App\Services\Providers\ProviderInterface;
use Carbon\Carbon;
use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DS88Provider implements ProviderInterface
{

    //status codes 
    const STATUS_CODE_SUCCESS = '00';

    const STATUS_CODE_INVALID_TOKEN = '01';

    const STATUS_CODE_INVALID_IP = '02';

    const STATUS_CODE_INVALID_TRANSACTION_CODE = '04';

    const STATUS_CODE_SUCCESS_BUT_CONTENT_FAILED = '05';

    const STATUS_CODE_ACCOUNT_DOESNT_EXIST = '11';

    const STATUS_CODE_THE_ORDER_NUMBER_HAS_BEEN_EXECUTED = '21';

    const STATUS_CODE_BET_SLIP_NUMBER_DOESNT_EXIST = '22';

    const STATUS_CODE_INSUFFICIENT_BALANCE = '31';

    const STATUS_CODE_DATA_FORMAT_ERROR = '93';

    const STATUS_CODE_EXCEPTION = '99';

    //status description 
    const STATUS_DESC_SUCCESS = 'Success';

    const STATUS_DESC_INVALID_TOKEN = 'Invalid Token';

    const STATUS_DESC_INVALID_IP = 'Invalid IP';

    const STATUS_DESC_INVALID_TRANSACTION_CODE = 'Invalid transaction code (SLUG)';

    const STATUS_DESC_SUCCESS_BUT_CONTENT_FAILED = 'Success but content failed';

    const STATUS_DESC_ACCOUNT_DOESNT_EXIST = 'Account does not exist';

    const STATUS_DESC_CURRENCY_MISMATCH = 'Currency Mismatch';

    const STATUS_DESC_THE_ORDER_NUMBER_HAS_BEEN_EXECUTED = 'The order number has been executed';

    const STATUS_DESC_BET_SLIP_NUMBER_DOESNT_EXIST = 'Bet slip number does not exist';

    const STATUS_DESC_INSUFFICIENT_BALANCE = 'Insufficient balance';

    const STATUS_DESC_DATA_FORMAT_ERROR = 'Data format error';

    const STATUS_DESC_GAME_ITEM_DOESNT_EXIST = 'Game Item Doesn\'t Exist';

    const STATUS_DESC_ACTION_NOT_SUPPORTED = 'Action Not Supported';

    const STATUS_DESC_EXCEPTION = 'Other exceptions';

    // languages
    const LANG_EN = 'en';

    const LANG_VN = 'vi';

    protected $username;

    protected $currency;

    protected $password;

    protected $lang;

    protected $entry;

    protected $headers;

    protected $base_url;

    protected $auth;

    protected $secret_key;

    public function __construct(Player $player, $game_code)
    {
        $this->username = $player->user->user_name;
        $this->password = substr(md5($player->user->user_name), 0, 16);

        $this->currency = self::getGameCurrency($player->wallet->currency);
        $credentials = self::getCredential($this->currency);
        $this->auth = $credentials['auth'];
        $this->secret_key = $credentials['secret_key'];
        $this->base_url = $credentials['base_url'];

        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->auth,
        ];
        $this->lang = self::getGameLanguage($player->language);
        $this->entry = $game_code;
    }


    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        try {

            $data = [
                'login' => $this->username,
                'password' => $this->password,
                'lang' => $this->lang,
                'login' => $this->username,
                'entry' => $this->entry,
            ];

            $response = Http::withHeaders($this->headers)->post($this->base_url . '/api/merchant/player/login', $data);

            Log::info("DS88 DEBUG");
            Log::info(json_encode([
                'api_url' => $this->base_url . '/api/merchant/player/login',
                'request_body' => $data,
                'request_headers' => $this->headers,
                'response' => $response->json(),
            ]));

            return $response->body();
            
        } catch (\Throwable $exception) {
            Log::info('***************************************************************************************');
            Log::info('DS88 Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        try {

            $data = [
                'account' => $this->username,
                'password' => $this->password,
                'name' => $this->username,
            ];

            $response = Http::withHeaders($this->headers)->post($this->base_url . '/api/merchant/players', $data);

            return $response->body();

            // Log::info("UG REGISTER API RESULT");
            // return(json_encode([
            //     'api_url' => $this->base_url . '/api/merchant/players',
            //     'request_headers' => $this->headers,
            //     'request_data' => $data,
            //     'response' => $response->body()
            // ]));

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('DS88 Provider Call registerToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }


    public static function getCredential(DS88CurrencyEnums $ds88_currency)
    {
        $auth = null;
        $secret_key = null;

        switch ($ds88_currency) {
            case DS88CurrencyEnums::VNDK:
                $auth = Config::get('app.ds88_auth.vndk');
                $secret_key = Config::get('app.ds88_secret_key.vndk');
                break;
            case DS88CurrencyEnums::PHP:
                $auth = Config::get('app.ds88_auth.php');
                $secret_key = Config::get('app.ds88_secret_key.php');
                break;
            case DS88CurrencyEnums::INR:
                $auth = Config::get('app.ds88_auth.inr');
                $secret_key = Config::get('app.ds88_secret_key.inr');
                break;
        }

        return [
            'auth' => $auth,
            'secret_key' => $secret_key,
            'base_url' => Config::get('app.ds88_base_url'),
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

    public static function getSystemCurrency(DS88CurrencyEnums $currency): int
    {
        return match ($currency) {
            DS88CurrencyEnums::VNDK => GlobalConstants::CURRENCY_VNDK,
            DS88CurrencyEnums::PHP => GlobalConstants::CURRENCY_PHP,
            DS88CurrencyEnums::INR => GlobalConstants::CURRENCY_INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getGameCurrency($currency): DS88CurrencyEnums
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => DS88CurrencyEnums::VNDK,
            GlobalConstants::CURRENCY_PHP => DS88CurrencyEnums::PHP,
            GlobalConstants::CURRENCY_INR => DS88CurrencyEnums::INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function authorizeProvider($token, DS88CurrencyEnums $requested_currency)
    {
        $credentials = self::getCredential($requested_currency);
        return $token === $credentials['auth'];
    }

    public static function roundBalance($balance)
    {
        return round($balance, 4);
    }

    public static function unknownError(): DS88SeamlessResponseDTO
    {
        return new DS88SeamlessResponseDTO(self::unknownErrorResponse(), 200);
    }

    public static function authFailed(): DS88SeamlessResponseDTO
    {
        return new DS88SeamlessResponseDTO(self::authFailedResponse(), 200);
    }

    public static function validationError($error): DS88SeamlessResponseDTO
    {
        return new DS88SeamlessResponseDTO(self::validationErrorResponse($error), 200);
    }

    public static function ipNotAllowed($ip): DS88SeamlessResponseDTO
    {
        return new DS88SeamlessResponseDTO(self::ipNotAllowedResponse($ip), 200);
    }

    public static function walletAccess($request_data, DS88ActionsEnums $wallet_action, DS88CurrencyEnums $requested_currency, $account): DS88SeamlessResponseDTO
    {
        $game_item = GameItem::where('game_id', GamePlatformConstants::DS888_GAME_CODE_COCKFIGHT)->first();

        if (!$game_item) {

            return new DS88SeamlessResponseDTO(self::gameItemNotFoundResponse(), 400);
        }

        if ($wallet_action == DS88ActionsEnums::BALANCE) {

            return self::getBalance($requested_currency, $account);
        }

        $is_dup_transaction = GameTransactionHistory::referenceNo($request_data['slug'])->gamePlatformId($game_item->gamePlatform->id)->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

        if ($is_dup_transaction) {

            return new DS88SeamlessResponseDTO(self::duplicatedRequest(), 400);
        }

        $data = $request_data['data'];

        $user_names = collect($data)->pluck('player')->unique();

        $users = User::whereIn('user_name', $user_names)->with('player')->get()->keyBy('user_name');

        $success_result = [];

        $error_result = [];

        foreach ($data as $record) {

            $user = $users->get($record['player']);

            if (!$user) {

                $error_result[] = self::userNotFoundResponse();

                continue;
            }

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $request_data['slug'],
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
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    $record['amount'],
                    true,
                    self::STATUS_DESC_CURRENCY_MISMATCH,
                );

                $error_result[] = self::currencyMismatchTransactionalResponse($record['num'], $record['player'], $locked_wallet->balance);

                continue;
            }

            match ($wallet_action) {
                DS88ActionsEnums::BET => self::placeBet(
                    $request_data,
                    $record,
                    $game_item,
                    $game_transaction_history,
                    $player_balance_history,
                    $player,
                    $locked_wallet,
                    $error_result,
                    $success_result
                ),
                DS88ActionsEnums::CANCEL => self::cancelBet(
                    $request_data,
                    $record,
                    $game_item,
                    $game_transaction_history,
                    $player_balance_history,
                    $player,
                    $locked_wallet,
                    $error_result,
                    $success_result
                ),
                DS88ActionsEnums::SETTLE => self::settleBet(
                    $request_data,
                    $record,
                    $game_item,
                    $game_transaction_history,
                    $player_balance_history,
                    $player,
                    $locked_wallet,
                    $error_result,
                    $success_result
                ),
                default => new DS88ActionsEnums(self::actionNotSupportedResponse(), 500),
            };
        }
        return new DS88SeamlessResponseDTO(self::placeBetResponse($success_result, $error_result), empty($errors) ? 200 : 400);
    }

    private static function settleBet(
        $request_data,
        $record,
        $game_item,
        $game_transaction_history,
        $player_balance_history,
        $player,
        $locked_wallet,
        &$error_result,
        &$success_result
    ) {

        $player_bet = $game_item->bets()
            ->status(BetConstants::STATUS_UNSETTLED)
            ->reference($record['num'])
            ->playerId($locked_wallet->player_id)
            ->first();

        if (!$player_bet) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                0,
                false,
                self::STATUS_DESC_BET_SLIP_NUMBER_DOESNT_EXIST,
            );

            $error_result[] = self::invalidBetTransactionalResponse($record['num'], $record['player'], $locked_wallet->balance);

            return;
        }

        $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

        $credit_amount = $record['amount'];

        $locked_wallet->credit($credit_amount);

        $bet_round = $player_bet->betRound;

        $win_loss = $credit_amount - $player_bet->bet_amount;

        $player_bet->settle($credit_amount, now()->toDateTimeString());

        $bet_round->close(
            now()->toDateTimeString(),
            $win_loss,
            $player_bet->turnover,
            $player_bet->valid_bet,
            $credit_amount,
        );

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
            $credit_amount,
            false,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION,
            null,
            $player_bet->id,
            $refer_transaction->id,
        );

        $player_balance_history->gameActionSuccess(
            $credit_amount,
            false,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION
        );

        $success_result[] = self::successTransactionalResponse($record['num'], $record['player'], $locked_wallet->balance);

        return;
    }

    private static function cancelBet(
        $request_data,
        $record,
        $game_item,
        $game_transaction_history,
        $player_balance_history,
        $player,
        $locked_wallet,
        &$error_result,
        &$success_result
    ) {
        $player_bet = $game_item->bets()
            ->status(BetConstants::STATUS_UNSETTLED)
            ->reference($record['num'])
            ->playerId($locked_wallet->player_id)
            ->first();

        if (!$player_bet) {

            $success_result[] = self::successTransactionalResponse($record['num'], $record['player'], $locked_wallet->balance);

            return;
        }

        $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

        $credit_amount = $record['amount'];

        $locked_wallet->credit($credit_amount);

        $player_bet->cancel(now()->toDateTimeString());

        $player_bet->betRound->close(now()->toDateTimeString(), null);

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
            $credit_amount,
            false,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION,
            null,
            $player_bet->id,
            $refer_transaction->id,
        );

        $player_balance_history->gameActionSuccess(
            $credit_amount,
            false,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION
        );

        $success_result[] = self::successTransactionalResponse($record['num'], $record['player'], $locked_wallet->balance);

        return;
    }

    private static function placeBet(
        $request_data,
        $record,
        $game_item,
        $game_transaction_history,
        $player_balance_history,
        $player,
        $locked_wallet,
        &$error_result,
        &$success_result
    ) {

        $debit_amount = abs($record['amount']);

        if ($locked_wallet->balance < $debit_amount) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $debit_amount,
                true,
                self::STATUS_DESC_INSUFFICIENT_BALANCE,
            );

            $error_result[] = self::insufficientFundsTransactionalResponse($record['num'], $record['player'], $locked_wallet->balance);

            return;
        }

        $locked_wallet->debit($debit_amount);

        $bet_round = BetRound::begin(
            $player->id,
            $game_item->gamePlatform->id,
            $record['num'],
            now()->toDateTimeString(),
            $locked_wallet->currency,
        );

        $player_bet = Bet::place(
            $debit_amount,
            null,
            $record['num'],
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

        $success_result[] = self::successTransactionalResponse($record['num'], $record['player'], $locked_wallet->balance);

        return;
    }

    private static function getBalance(DS88CurrencyEnums $requested_currency, $account): DS88SeamlessResponseDTO
    {
        $user = User::where('user_name', $account)->first();

        if (!$user) {
            return new DS88SeamlessResponseDTO(self::userNotFoundResponse(), 400);
        }

        $player = $user->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if ($player_game_currency !== $requested_currency) {
            return new DS88SeamlessResponseDTO(self::currencyMismatchResponse(), 400);
        }

        return new DS88SeamlessResponseDTO(self::successBalanceResponse($locked_wallet->balance, $account), 200);
    }

    private static function placeBetResponse($data, $errors)
    {
        empty($errors) ? $type = self::STATUS_CODE_SUCCESS : $type = self::STATUS_CODE_EXCEPTION;

        return [
            'code' => $type,
            'data' => $data,
            'error' => $errors,
        ];
    }

    private static function successBalanceResponse($balance, $account)
    {
        return [
            'code' => self::STATUS_CODE_SUCCESS,
            'balance' => self::roundBalance($balance),
            'player' => $account,
        ];
    }

    private static function successTransactionalResponse($num, $player, $balance)
    {
        return [
            'code' => self::STATUS_CODE_SUCCESS,
            'num' => $num,
            'player' => $player,
            'balance' => self::roundBalance($balance),
            'message' => self::STATUS_DESC_SUCCESS,
        ];
    }

    private static function invalidBetTransactionalResponse($num, $player, $balance)
    {
        return [
            'code' => self::STATUS_CODE_BET_SLIP_NUMBER_DOESNT_EXIST,
            'num' => $num,
            'player' => $player,
            'balance' => self::roundBalance($balance),
            'message' => self::STATUS_DESC_BET_SLIP_NUMBER_DOESNT_EXIST,
        ];
    }


    private static function insufficientFundsTransactionalResponse($num, $player, $balance)
    {
        return [
            'code' => self::STATUS_CODE_INSUFFICIENT_BALANCE,
            'num' => $num,
            'player' => $player,
            'balance' => self::roundBalance($balance),
            'message' => self::STATUS_DESC_INSUFFICIENT_BALANCE,
        ];
    }

    private static function currencyMismatchTransactionalResponse($num, $player, $balance)
    {
        return [
            'code' => self::STATUS_CODE_EXCEPTION,
            'num' => $num,
            'player' => $player,
            'balance' => self::roundBalance($balance),
            'message' => self::STATUS_DESC_CURRENCY_MISMATCH,
        ];
    }

    private static function currencyMismatchResponse()
    {
        return [
            'code' => self::STATUS_CODE_EXCEPTION,
            'message' => self::STATUS_DESC_CURRENCY_MISMATCH,
        ];
    }

    private static function duplicatedRequest()
    {
        return [
            'code' => self::STATUS_CODE_THE_ORDER_NUMBER_HAS_BEEN_EXECUTED,
            'message' => self::STATUS_DESC_THE_ORDER_NUMBER_HAS_BEEN_EXECUTED,
        ];
    }

    private static function userNotFoundResponse()
    {
        return [
            'code' => self::STATUS_CODE_ACCOUNT_DOESNT_EXIST,
            'message' => self::STATUS_DESC_ACCOUNT_DOESNT_EXIST,
        ];
    }

    private static function actionNotSupportedResponse()
    {
        return [
            'code' => self::STATUS_CODE_EXCEPTION,
            'message' => self::STATUS_DESC_ACTION_NOT_SUPPORTED,
        ];
    }

    private static function gameItemNotFoundResponse()
    {
        return [
            'code' => self::STATUS_CODE_EXCEPTION,
            'message' => self::STATUS_DESC_GAME_ITEM_DOESNT_EXIST,
        ];
    }

    private static function unknownErrorResponse()
    {
        return [
            'code' => self::STATUS_CODE_EXCEPTION,
            'message' => self::STATUS_DESC_EXCEPTION,
        ];
    }

    private static function authFailedResponse()
    {
        return [
            'code' => self::STATUS_CODE_INVALID_TOKEN,
            'message' => self::STATUS_DESC_INVALID_TOKEN,
        ];
    }

    private static function validationErrorResponse($error)
    {
        return [
            'code' => self::STATUS_CODE_DATA_FORMAT_ERROR,
            'message' => self::STATUS_DESC_DATA_FORMAT_ERROR,
            'error' => $error,
        ];
    }

    private static function ipNotAllowedResponse($ip)
    {
        return [
            'code' => self::STATUS_CODE_INVALID_IP,
            'message' => self::STATUS_DESC_INVALID_IP,
            'ip' => $ip,
        ];
    }
}
