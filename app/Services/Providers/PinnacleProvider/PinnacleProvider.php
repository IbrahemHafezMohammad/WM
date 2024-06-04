<?php

namespace App\Services\Providers\PinnacleProvider;

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
use App\Services\Providers\PinnacleProvider\Enums\PinnacleActionsEnums;
use App\Services\Providers\PinnacleProvider\Enums\PinnacleCurrencyEnums;
use App\Services\Providers\PinnacleProvider\DTOs\PinnacleSeamlessResponseDTO;
use App\Services\Providers\PinnacleProvider\Encryption\AesCbcEncryptor;

class PinnacleProvider implements ProviderInterface
{
    //status
    const STATUS_SUCCESS = 0;
    const STATUS_UNKNOWN_ERROR = -1;
    const STATUS_INSUFFICIENT_FUNDS = -2;
    const STATUS_ACCOUNT_NOT_FOUND = -5;
    const STATUS_AUTH_FAILED = -6;
    const STATUS_TRANSACTION_NOT_COMPLETE = -7;
    const STATUS_TRANSACTION_NOT_FOUND = -8;
    const STATUS_CURRENCY_MISMATCH = -9;

    //status message
    const STATUS_MESSAGE_SUCCESS = 'SUCCESS';
    const STATUS_MESSAGE_UNKNOWN_ERROR = 'UNKNOWN_ERROR';
    const STATUS_MESSAGE_INSUFFICIENT_FUNDS = 'INSUFFICIENT_FUNDS';
    const STATUS_MESSAGE_ACTION_NOT_ALLOWED = 'ACTION_NOT_ALLOWED';
    const STATUS_MESSAGE_TRANSACTION_NOT_COMPLETE = 'TRANSACTION_NOT_COMPLETE';
    const STATUS_MESSAGE_TRANSACTION_NOT_FOUND = 'TRANSACTION_NOT_FOUND';
    const STATUS_MESSAGE_WAGER_NOT_FOUND = 'WAGER_NOT_FOUND';
    const STATUS_MESSAGE_CURRENCY_MISMATCH = 'CURRENCY_MISMATCH';
    const STATUS_MESSAGE_ACCOUNT_NOT_FOUND = 'ACCOUNT_NOT_FOUND';
    const STATUS_MESSAGE_AUTH_FAILED = 'AUTH_FAILED';
    const STATUS_MESSAGE_IP_WHITELIST = 'IP_WHITELIST';
    const STATUS_MESSAGE_VALIDATION_ERROR = 'VALIDATION_ERROR';
    const STATUS_MESSAGE_GAME_NOT_FOUND = 'GAME_NOT_FOUND';
    const STATUS_MESSAGE_ACTION_NOT_SUPPORTER = 'ACTION_NOT_SUPPORTER';
    const STATUS_MESSAGE_TRANSACTION_ACTION_NOT_SUPPORTER = 'TRANSACTION_ACTION_NOT_SUPPORTER';
    const STATUS_MESSAGE_DUPLICATED_TRANSACTION = 'DUPLICATED_TRANSACTION';

    //transaction actions
    const TRANSACTION_ACTION_BETTED = 'BETTED';
    const TRANSACTION_ACTION_ACCEPTED  = 'ACCEPTED';
    const TRANSACTION_ACTION_ROLLBACKED  = 'ROLLBACKED';
    const TRANSACTION_ACTION_REJECTED  = 'REJECTED';
    const TRANSACTION_ACTION_SETTLED  = 'SETTLED';
    const TRANSACTION_ACTION_CANCELLED  = 'CANCELLED';
    const TRANSACTION_ACTION_UNSETTLED  = 'UNSETTLED';

    // bet types
    const BET_TYPE_SINGLE = 'SINGLE';
    const BET_TYPE_PARLAY = 'PARLAY';

    // bet formats
    const BET_FORMAT_ML_1X2 = 1;
    const BET_FORMAT_HDP = 2;
    const BET_FORMAT_OU = 3;
    const BET_FORMAT_HOME_TOTALS = 4;
    const BET_FORMAT_AWAY_TOTALS = 5;
    const BET_FORMAT_MIX_PARLAY = 6;
    const BET_FORMAT_TEASER = 7;
    const BET_FORMAT_MANUAL_PLAY = 8;
    const BET_FORMAT_OE = 97;
    const BET_FORMAT_SPECIAL_OUTRIGHT = 99;

    // odds formats
    const ODDS_FORMAT_AM = 0;
    const ODDS_FORMAT_EU = 1;
    const ODDS_FORMAT_HK = 2;
    const ODDS_FORMAT_ID = 3;
    const ODDS_FORMAT_MY = 4;

    // transaction types
    const TRANSACTION_TYPE_DEBIT = 'DEBIT';
    const TRANSACTION_TYPE_CREDIT = 'CREDIT';

    //languages
    const LANG_EN = 'en';
    const LANG_VN = 'vi';
    const LANG_HI = 'hi';

    protected $user_id;

    protected $currency;

    protected $base_url;

    protected $agent_code;

    protected $agent_key;

    protected $secret_key;

    protected $client_url;

    protected $headers;

    protected $token;

    protected $token_payload;

    protected $language;

    function __construct(Player $player, $game_code)
    {
        $this->user_id = $player->user->user_name;

        $this->currency = self::getGameCurrency($player->wallet->currency);
        $credentials = self::getCredential($this->currency);
        $this->base_url = $credentials['base_url'];
        $this->agent_code = $credentials['agent_code'];
        $this->agent_key = $credentials['agent_key'];
        $this->secret_key = $credentials['secret_key'];
        $this->client_url = $credentials['client_url'];

        $encryptor = new AesCbcEncryptor($this->agent_code, $this->agent_key, $this->secret_key);

        $encoded = $encryptor->encode();

        $this->token = $encoded['token'];

        $this->token_payload = $encoded['token_payload'];

        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'userCode' => $this->agent_code,
            'token' => $this->token,
        ];

        $this->language = self::getGameLanguage($player->language);
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        try {

            $data = [
                'loginId' => $this->user_id,
                'local' => $this->language,
                'parentUrl' => $this->client_url,
            ];

            $response = Http::withHeaders($this->headers)->post($this->base_url . '/player/loginV2', $data);

            // Log::info('pinnacle loginToGame API Call');
            // Log::info(json_encode([
            //     'secret_key' => $this->secret_key,
            //     'agent_key' => $this->agent_key,
            //     'agent_code' => $this->agent_code,
            //     'token_payload' => $this->token_payload,
            //     'api_url' => $this->base_url . '/player/loginV2',
            //     'request_headers' => $this->headers,
            //     'request_data' => $data,
            //     'response' => $response->body()
            // ]));

            return $response->body();
        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('Pinnacle Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        throw new Exception('NOT_SUPPORTED');
    }

    public static function getGameLanguage($language)
    {
        return match ($language) {
            GlobalConstants::LANG_EN => self::LANG_EN,
            GlobalConstants::LANG_VN => self::LANG_VN,
            GlobalConstants::LANG_HI => self::LANG_HI,
            default => self::LANG_EN,
        };
    }

    public static function getCredential(PinnacleCurrencyEnums $pinnacle_currency)
    {
        $agent_code = null;
        $agent_key = null;
        $secret_key = null;
        $client_url = null;

        switch ($pinnacle_currency) {
            case PinnacleCurrencyEnums::VNDK:
                $agent_code = Config::get('app.pinnacle_agent_code.vndk');
                $agent_key = Config::get('app.pinnacle_agent_key.vndk');
                $secret_key = Config::get('app.pinnacle_secret_key.vndk');
                $client_url = Config::get('app.pinnacle_client_url.vndk');
                break;
            case PinnacleCurrencyEnums::PHP:
                $agent_code = Config::get('app.pinnacle_agent_code.php');
                $agent_key = Config::get('app.pinnacle_agent_key.php');
                $secret_key = Config::get('app.pinnacle_secret_key.php');
                $client_url = Config::get('app.pinnacle_client_url.php');
                break;
            case PinnacleCurrencyEnums::INR:
                $agent_code = Config::get('app.pinnacle_agent_code.inr');
                $agent_key = Config::get('app.pinnacle_agent_key.inr');
                $secret_key = Config::get('app.pinnacle_secret_key.inr');
                $client_url = Config::get('app.pinnacle_client_url.inr');
                break;
        }

        return [
            'base_url' => Config::get('app.pinnacle_base_url'),
            'agent_code' => $agent_code,
            'agent_key' => $agent_key,
            'secret_key' => $secret_key,
            'client_url' => $client_url,
        ];
    }

    public static function getSystemCurrency(PinnacleCurrencyEnums $currency): int
    {
        return match ($currency) {
            PinnacleCurrencyEnums::VNDK => GlobalConstants::CURRENCY_VNDK,
            PinnacleCurrencyEnums::PHP => GlobalConstants::CURRENCY_PHP,
            PinnacleCurrencyEnums::INR => GlobalConstants::CURRENCY_INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getGameCurrency($currency): PinnacleCurrencyEnums
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => PinnacleCurrencyEnums::VNDK,
            GlobalConstants::CURRENCY_PHP => PinnacleCurrencyEnums::PHP,
            GlobalConstants::CURRENCY_INR => PinnacleCurrencyEnums::INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function roundBalance($balance)
    {
        return round($balance, 4);
    }

    public static function transactionActions()
    {
        return [
            self::TRANSACTION_ACTION_BETTED,
            self::TRANSACTION_ACTION_ACCEPTED,
            self::TRANSACTION_ACTION_ROLLBACKED,
            self::TRANSACTION_ACTION_REJECTED,
            self::TRANSACTION_ACTION_SETTLED,
            self::TRANSACTION_ACTION_CANCELLED,
            self::TRANSACTION_ACTION_UNSETTLED,
        ];
    }

    public static function getBetFormats()
    {
        return [
            self::BET_FORMAT_ML_1X2 => '1x2 each way win handicap',
            self::BET_FORMAT_HDP => 'Handicap',
            self::BET_FORMAT_OU => 'Over Under',
            self::BET_FORMAT_HOME_TOTALS => 'Home Team_Total Score',
            self::BET_FORMAT_AWAY_TOTALS => 'Away Team_Total Score',
            self::BET_FORMAT_MIX_PARLAY => 'mixed pass',
            self::BET_FORMAT_TEASER => 'Curriculum Handicap',
            self::BET_FORMAT_MANUAL_PLAY => 'Manual betting',
            self::BET_FORMAT_OE => 'Odd Even',
            self::BET_FORMAT_SPECIAL_OUTRIGHT => 'Special Betting/Outstanding Champion',
        ];
    }

    public static function getOddsFormats()
    {
        return [
            self::ODDS_FORMAT_AM => 'American odds format',
            self::ODDS_FORMAT_EU => 'Euro odds format',
            self::ODDS_FORMAT_HK => 'Hong Kong odds format',
            self::ODDS_FORMAT_ID => 'Indo odds format',
            self::ODDS_FORMAT_MY => 'Malay odds format',
        ];
    }

    public static function authorizeProvider($signature, PinnacleCurrencyEnums $pinnacle_currency)
    {
        // Log::info('PinnacleProvider AuthorizeProvider');
        $credentials = self::getCredential($pinnacle_currency);
        $agent_code = $credentials['agent_code'];
        $agent_key = $credentials['agent_key'];
        $secret_key = $credentials['secret_key'];

        $encryptor = new AesCbcEncryptor($agent_code, $agent_key, $secret_key);

        $decoded = $encryptor->decode($signature);

        if (is_null($decoded)) {
            return false;
        }

        if ($decoded['agentCode'] !== $agent_code) {
            return false;
        }

        $timestamp = $decoded['timestamp'];

        $hashed_token = md5($agent_code . $timestamp . $agent_key);

        if ($hashed_token !== $decoded['hashToken']) {
            return false;
        }

        return true;
    }

    public static function ipNotAllowed($ip): PinnacleSeamlessResponseDTO
    {
        return new PinnacleSeamlessResponseDTO(self::ipNotAllowedResponse($ip), 200);
    }

    public static function authFailed(): PinnacleSeamlessResponseDTO
    {
        return new PinnacleSeamlessResponseDTO(self::authFailedResponse(), 200);
    }

    public static function unknownError(): PinnacleSeamlessResponseDTO
    {
        return new PinnacleSeamlessResponseDTO(self::unknownErrorResponse(), 200);
    }

    public static function validationError($error): PinnacleSeamlessResponseDTO
    {
        return new PinnacleSeamlessResponseDTO(self::validationErrorResponse($error), 200);
    }


    public static function walletAccess($request_data, PinnacleActionsEnums $action, PinnacleCurrencyEnums $currency): PinnacleSeamlessResponseDTO
    {
        $game_item = GameItem::where('game_id', GamePlatformConstants::PINNACLE_GAME_CODE_LOBBY)->first();

        if (!$game_item) {

            return new PinnacleSeamlessResponseDTO(self::gameItemNotFoundResponse(), 200);
        }

        return match ($action) {
            PinnacleActionsEnums::PING => new PinnacleSeamlessResponseDTO(self::pingResponse(), 200, [], false),
            PinnacleActionsEnums::BALANCE => self::getBalance($request_data, $game_item, $currency),
            PinnacleActionsEnums::WAGERING => self::changeBalance($request_data, $game_item, $currency),
            default => new PinnacleSeamlessResponseDTO(self::actionNotSupportedResponse(), 200),
        };
    }

    private static function changeBalance($request_data, $game_item, PinnacleCurrencyEnums $requested_currency): PinnacleSeamlessResponseDTO
    {
        $request_id = $request_data['route_params']['requestid'];

        $result = [];

        $actions = $request_data['Actions'];

        foreach ($actions as $action) {

            $user = User::where('user_name', $action['PlayerInfo']['LoginId'])->first();

            $reference_no = $action['Transaction']['TransactionId'] ?? null;

            if (!$user) {

                $result[] = self::actionUserNotFoundResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
            }

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if ($player_game_currency !== $requested_currency) {

                $result[] = self::actionCurrencyMismatchResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
            }

            $dup_transaction = GameTransactionHistory::transactionRequestNo($request_id)
                ->orGameTransactionNo($action['Id'])
                ->gamePlatformId($game_item->gamePlatform->id)
                ->status(GameTransactionHistoryConstants::STATUS_SUCCESS)
                ->first();

            if ($dup_transaction) {

                Log::info("PinnacleProvider ChangeBalance Duplicated Transaction : $dup_transaction");
                $result[] = self::duplicatedTransactionResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);

                continue;
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $reference_no,
                $request_id,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $result[] = match ($action['Name']) {
                self::TRANSACTION_ACTION_BETTED => self::bet($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data),
                self::TRANSACTION_ACTION_ACCEPTED => self::acceptBet($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data),
                self::TRANSACTION_ACTION_SETTLED => self::settle($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data),
                self::TRANSACTION_ACTION_REJECTED,
                self::TRANSACTION_ACTION_ROLLBACKED => self::reject($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data),
                self::TRANSACTION_ACTION_CANCELLED => self::cancel($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data),
                self::TRANSACTION_ACTION_UNSETTLED => self::unsettle($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data),
                default => self::transactionActionNotSupportedResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']),
            };
        }

        return new PinnacleSeamlessResponseDTO(self::successResponse($result, $request_data['route_params']['usercode'], $locked_wallet->balance), 200);
    }

    private static function unsettle($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data)
    {
        $reference_no = $action['Transaction']['TransactionId'] ?? null;

        $player_bet = $game_item->bets()
            ->statusIn([BetConstants::STATUS_SETTLED, BetConstants::STATUS_CANCELED])
            ->reference($action['WagerInfo']['WagerId'])
            ->playerId($player->id)
            ->first();

        $refer_transaction = $player_bet?->latestSuccessfulGameTransactionHistory;

        if (
            !$player_bet ||
            ($player_bet->status == BetConstants::STATUS_CANCELED && $refer_transaction->transaction_type != GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL)
            ) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                0,
                false,
                self::STATUS_MESSAGE_WAGER_NOT_FOUND,
            );

            return self::betNotFoundResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
        }

        $amount = 0;

        if (isset($action['Transaction'])) {

            $amount = abs($action['Transaction']['Amount']);

            if ($action['Transaction']['TransactionType'] == self::TRANSACTION_TYPE_CREDIT) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    $amount,
                    false,
                    self::STATUS_MESSAGE_ACTION_NOT_ALLOWED,
                );

                return self::actionNotAllowedResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
            }

            $locked_wallet->debit($amount);
        }

        $bet_round = $player_bet->betRound;

        $player_bet->unsettle();

        $bet_round->reopen(null, $bet_round->total_turnovers, $bet_round->total_valid_bets, null);

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_UNSETTLE,
            $amount,
            true,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_TYPE_UNSETTLE_BET,
            $action['Id'],
            $player_bet->id,
            $refer_transaction->id,
        );

        $player_balance_history->gameActionSuccess(
            $amount,
            true,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_TYPE_UNSETTLE_BET
        );

        return self::successActionResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
    }

    private static function reject($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data)
    {
        $reference_no = $action['Transaction']['TransactionId'];

        $player_bet = $game_item->bets()
            ->status(BetConstants::STATUS_UNSETTLED)
            ->reference($action['WagerInfo']['WagerId'])
            ->playerId($player->id)
            ->first();

        $refer_transaction = $player_bet?->latestSuccessfulGameTransactionHistory;

        $amount = abs($action['Transaction']['Amount']);

        if (
            (!$player_bet && $action['Name'] == self::TRANSACTION_ACTION_REJECTED) ||
            ($player_bet && $refer_transaction->transaction_type != GameTransactionHistoryConstants::TRANSACTION_TYPE_BET)
        ) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                0,
                false,
                self::STATUS_MESSAGE_WAGER_NOT_FOUND,
            );

            return self::betNotFoundResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
        } elseif (!$player_bet && $action['Name'] == self::TRANSACTION_ACTION_ROLLBACKED) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                $amount,
                false,
                self::STATUS_MESSAGE_ACTION_NOT_ALLOWED,
            );

            return self::successActionResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
        }


        if ($action['Name'] == self::TRANSACTION_ACTION_ROLLBACKED && $refer_transaction->reference_no != $action['Transaction']['ReferTransactionId'] ?? null) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                $amount,
                false,
                self::STATUS_MESSAGE_ACTION_NOT_ALLOWED,
            );

            return self::transactionNotCompleteResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
        }


        if ($action['Transaction']['TransactionType'] == self::TRANSACTION_TYPE_DEBIT) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                $amount,
                false,
                self::STATUS_MESSAGE_ACTION_NOT_ALLOWED,
            );

            return self::actionNotAllowedResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
        }

        $locked_wallet->credit($amount);

        $timestamp = Carbon::parse($action['Transaction']['TransactionDate'], 'Etc/GMT-4')->setTimezone('UTC')->toDateTimeString();

        $bet_round = $player_bet->betRound;

        $player_bet->cancel($timestamp);

        $bet_round->close($timestamp, null);

        $note = $action['Name'] == self::TRANSACTION_ACTION_REJECTED ? GameTransactionHistoryConstants::NOTE_REJECTION_TRANSACTION : GameTransactionHistoryConstants::NOTE_ROLLBACK_TRANSACTION;

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_REJECT_OR_ROLLBACK,
            $amount,
            false,
            $locked_wallet->balance,
            $note,
            $action['Id'],
            $player_bet->id,
            $refer_transaction->id,
        );

        $player_balance_history->gameActionSuccess(
            $amount,
            false,
            $locked_wallet->balance,
            $game_transaction_history->id,
            $note
        );

        return self::successActionResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
    }

    private static function cancel($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data)
    {
        $reference_no = $action['Transaction']['TransactionId'];

        $player_bet = $game_item->bets()
            ->statusIn([BetConstants::STATUS_ACCEPTED, BetConstants::STATUS_UNSETTLED])
            ->reference($action['WagerInfo']['WagerId'])
            ->playerId($player->id)
            ->first();

        $refer_transaction = $player_bet?->latestSuccessfulGameTransactionHistory;

        if (
            !$player_bet ||
            (
                $player_bet->status == BetConstants::STATUS_UNSETTLED &&
                $refer_transaction->transaction_type != GameTransactionHistoryConstants::TRANSACTION_TYPE_UNSETTLE
            )
        ) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                0,
                false,
                self::STATUS_MESSAGE_WAGER_NOT_FOUND,
            );

            return self::betNotFoundResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
        }

        $amount = abs($action['Transaction']['Amount']);

        if ($action['Transaction']['TransactionType'] == self::TRANSACTION_TYPE_DEBIT) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                $amount,
                false,
                self::STATUS_MESSAGE_ACTION_NOT_ALLOWED,
            );

            return self::actionNotAllowedResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
        }

        $locked_wallet->credit($amount);

        $timestamp = Carbon::parse($action['Transaction']['TransactionDate'], 'Etc/GMT-4')->setTimezone('UTC')->toDateTimeString();

        $bet_round = $player_bet->betRound;

        $player_bet->cancel($timestamp);

        $bet_round->close($timestamp, null);

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
            $amount,
            false,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION,
            $action['Id'],
            $player_bet->id,
            $refer_transaction->id,
        );

        $player_balance_history->gameActionSuccess(
            $amount,
            false,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION
        );

        return self::successActionResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
    }

    private static function settle($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data)
    {
        $reference_no = $action['Transaction']['TransactionId'] ?? null;

        $player_bet = $game_item->bets()
            ->statusIn([BetConstants::STATUS_ACCEPTED, BetConstants::STATUS_UNSETTLED])
            ->reference($action['WagerInfo']['WagerId'])
            ->playerId($player->id)
            ->first();

        $refer_transaction = $player_bet?->latestSuccessfulGameTransactionHistory;

        if (
            !$player_bet ||
            (
                $player_bet->status == BetConstants::STATUS_UNSETTLED &&
                $refer_transaction->transaction_type != GameTransactionHistoryConstants::TRANSACTION_TYPE_UNSETTLE
            )
        ) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                0,
                false,
                self::STATUS_MESSAGE_WAGER_NOT_FOUND,
            );

            return self::betNotFoundResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
        }

        $amount = 0;

        if (isset($action['Transaction'])) {

            $amount = abs($action['Transaction']['Amount']);

            if ($action['Transaction']['TransactionType'] == self::TRANSACTION_TYPE_DEBIT) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    $amount,
                    false,
                    self::STATUS_MESSAGE_ACTION_NOT_ALLOWED,
                );

                return self::actionNotAllowedResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
            }

            $locked_wallet->credit($amount);
        }

        $bet_round = $player_bet->betRound;

        $win_loss = $action['WagerInfo']['ProfitAndLoss'];

        $timestamp = Carbon::parse($action['WagerInfo']['SettlementTime'], 'Etc/GMT-4')->setTimezone('UTC')->toDateTimeString();

        $game_info = json_encode([
            'Legs' => $action['WagerInfo']['Legs'] ?? null,
            'BetType' => self::getBetFormats()[$action['WagerInfo']['BetType']] ?? $action['WagerInfo']['BetType'],
            'OddsFormat' => self::getOddsFormats()[$action['WagerInfo']['OddsFormat']] ?? $action['WagerInfo']['OddsFormat'],
            'ToWin' => $action['WagerInfo']['ToWin'],
            'WagerMasterId' => $action['WagerInfo']['WagerMasterId'] ?? null,
            'WagerNum' => $action['WagerInfo']['WagerNum'] ?? null,
            'RoundRobinOptions' => $action['WagerInfo']['RoundRobinOptions'] ?? null,
        ]);

        $player_bet->settle($amount, $timestamp, odds: $action['WagerInfo']['Odds'], win_loss: $win_loss, game_info: $game_info);

        $bet_round->close(
            $timestamp,
            $win_loss,
            $player_bet->turnover,
            $player_bet->valid_bet,
            $amount,
        );

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
            $amount,
            false,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION . ' ' . $action['WagerInfo']['Outcome'] ?? null,
            $action['Id'],
            $player_bet->id,
            $refer_transaction?->id
        );

        $player_balance_history->gameActionSuccess(
            $amount,
            false,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION . ' ' . $action['WagerInfo']['Outcome'] ?? null
        );

        return self::successActionResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
    }

    private static function acceptBet($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data)
    {
        $reference_no = $action['Transaction']['TransactionId'] ?? null;

        $player_bet = $game_item->bets()
            ->status(BetConstants::STATUS_UNSETTLED)
            ->reference($action['WagerInfo']['WagerId'])
            ->playerId($player->id)
            ->first();

        if (!$player_bet) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                0,
                false,
                self::STATUS_MESSAGE_WAGER_NOT_FOUND,
            );

            return self::betNotFoundResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
        }

        // if ($player_bet->status == BetConstants::STATUS_ACCEPTED) {
        //     return self::successActionResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
        // }

        $game_info = json_encode([
            'Legs' => $action['WagerInfo']['Legs'] ?? null,
            'BetType' => self::getBetFormats()[$action['WagerInfo']['BetType']] ?? $action['WagerInfo']['BetType'],
            'OddsFormat' => self::getOddsFormats()[$action['WagerInfo']['OddsFormat']] ?? $action['WagerInfo']['OddsFormat'],
            'ToWin' => $action['WagerInfo']['ToWin'],
            'WagerMasterId' => $action['WagerInfo']['WagerMasterId'] ?? null,
            'WagerNum' => $action['WagerInfo']['WagerNum'] ?? null,
            'RoundRobinOptions' => $action['WagerInfo']['RoundRobinOptions'] ?? null,
        ]);

        $player_bet->accept([
            'game_info' => $game_info,
            'odds' => $action['WagerInfo']['Odds'],
            'valid_bet' => $action['WagerInfo']['ToRisk'],
        ]);

        $is_withdraw = false;

        if (isset($action['Transaction'])) {

            $amount = abs($action['Transaction']['Amount']);

            if ($action['Transaction']['TransactionType'] == self::TRANSACTION_TYPE_CREDIT) {

                $locked_wallet->credit($amount);
            } elseif ($action['Transaction']['TransactionType'] == self::TRANSACTION_TYPE_DEBIT) {

                if ($locked_wallet->balance < $amount) {

                    $game_transaction_history->gameActionFailed(
                        GameTransactionHistoryConstants::TRANSACTION_TYPE_ACCEPT_BET,
                        $amount,
                        true,
                        self::STATUS_MESSAGE_INSUFFICIENT_FUNDS,
                    );

                    return self::insufficientFundsResponse($action['Id'], $action['Transaction']['TransactionId'], $action['WagerInfo']['WagerId']);
                }

                $is_withdraw = true;

                $locked_wallet->debit($amount);
            }

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACCEPT_BET,
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_TYPE_ACCEPT_BET,
                $action['Id'],
                $player_bet->id,
                $refer_transaction?->id
            );

            $player_balance_history->gameActionSuccess(
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_TYPE_ACCEPT_BET
            );
        }

        return self::successActionResponse($action['Id'], $reference_no, $action['WagerInfo']['WagerId']);
    }

    private static function bet($action, $game_item, $player, $locked_wallet, $game_transaction_history, $player_balance_history, $request_data)
    {
        $deduction_amount = abs($action['Transaction']['Amount']);

        if ($locked_wallet->balance < $deduction_amount) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $deduction_amount,
                true,
                self::STATUS_MESSAGE_INSUFFICIENT_FUNDS,
            );

            return self::insufficientFundsResponse($action['Id'], $action['Transaction']['TransactionId'], $action['WagerInfo']['WagerId']);
        }

        $locked_wallet->debit($deduction_amount);

        $timestamp = Carbon::parse($action['Transaction']['TransactionDate'], 'Etc/GMT-4')->setTimezone('UTC')->toDateTimeString();

        $bet_round = BetRound::begin(
            $player->id,
            $game_item->gamePlatform->id,
            $action['WagerInfo']['WagerId'],
            $timestamp,
            $locked_wallet->currency,
            ip_address: $action['WagerInfo']['PlayerIPAddress'],
        );

        // $game_info = json_encode([
        //     'Legs' => $action['WagerInfo']['Legs'] ?? null,
        //     'BetType' => self::getBetFormats()[$action['WagerInfo']['BetType']] ?? $action['WagerInfo']['BetType'],
        //     'OddsFormat' => self::getOddsFormats()[$action['WagerInfo']['OddsFormat']] ?? $action['WagerInfo']['OddsFormat'],
        //     'ToWin' => $action['WagerInfo']['ToWin'],
        //     'WagerMasterId' => $action['WagerInfo']['WagerMasterId'] ?? null,
        //     'WagerNum' => $action['WagerInfo']['WagerNum'] ?? null,
        //     'RoundRobinOptions' => $action['WagerInfo']['RoundRobinOptions'] ?? null,
        // ]);

        $player_bet = Bet::place(
            $deduction_amount,
            null,
            $action['WagerInfo']['WagerId'],
            $bet_round->id,
            $game_item->id,
            $timestamp,
            $locked_wallet->currency,
            $action['WagerInfo']['Odds'],
            game_code: $action['WagerInfo']['Sport'] ?? null,
            // game_info: $game_info,
        );

        $note = GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET . ' ' . $action['WagerInfo']['Type'] ?? null;

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
            $deduction_amount,
            true,
            $locked_wallet->balance,
            $note,
            $action['Id'],
            $player_bet->id,
        );

        $player_balance_history->gameActionSuccess(
            $deduction_amount,
            true,
            $locked_wallet->balance,
            $game_transaction_history->id,
            $note
        );

        return self::successActionResponse($action['Id'], $action['Transaction']['TransactionId'], $action['WagerInfo']['WagerId']);
    }

    private static function getBalance($request_data, $game_item, PinnacleCurrencyEnums $requested_currency): PinnacleSeamlessResponseDTO
    {
        $user = User::where('user_name', $request_data['route_params']['usercode'])->first();

        if (!$user) {

            return new PinnacleSeamlessResponseDTO(self::userNotFoundResponse(), 200);
        }

        $player = $user->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if ($player_game_currency !== $requested_currency) {
            return new PinnacleCurrencyEnums(self::currencyMismatchResponse(), 200);
        }

        return new PinnacleCurrencyEnums(self::balanceSuccessResponse($request_data['route_params']['usercode'], $locked_wallet->balance), 200);
    }

    private static function successResponse($result, $user_id, $balance)
    {
        return [
            'Result' => [
                'UserCode' => $user_id,
                'AvailableBalance' => self::roundBalance($balance),
                'Actions' => $result,
            ],
            'ErrorCode' => self::STATUS_SUCCESS,
            'Timestamp' => now()->setTimezone('Etc/GMT+4')->toDateTimeString(),
        ];
    }

    private static function successActionResponse($action_id, $transaction_id, $wager_id)
    {
        return [
            "Id" => $action_id,
            "TransactionId" => $transaction_id,
            "WagerId" => $wager_id,
            "ResponseCode" => self::STATUS_SUCCESS,
            // "ResponseMessage" => self::STATUS_MESSAGE_SUCCESS,
        ];
    }

    private static function betNotFoundResponse($action_id, $transaction_id, $wager_id)
    {
        return [
            "Id" => $action_id,
            "TransactionId" => $transaction_id,
            "WagerId" => $wager_id,
            "ResponseCode" => self::STATUS_TRANSACTION_NOT_FOUND,
            "ResponseMessage" => self::STATUS_MESSAGE_WAGER_NOT_FOUND,
        ];
    }

    private static function actionNotAllowedResponse($action_id, $transaction_id, $wager_id)
    {
        return [
            "Id" => $action_id,
            "TransactionId" => $transaction_id,
            "WagerId" => $wager_id,
            "ResponseCode" => self::STATUS_UNKNOWN_ERROR,
            "ResponseMessage" => self::STATUS_MESSAGE_ACTION_NOT_ALLOWED,
        ];
    }

    private static function transactionNotCompleteResponse($action_id, $transaction_id, $wager_id)
    {
        return [
            "Id" => $action_id,
            "TransactionId" => $transaction_id,
            "WagerId" => $wager_id,
            "ResponseCode" => self::STATUS_TRANSACTION_NOT_COMPLETE,
            "ResponseMessage" => self::STATUS_MESSAGE_TRANSACTION_NOT_COMPLETE,
        ];
    }

    private static function actionCurrencyMismatchResponse($action_id, $transaction_id, $wager_id)
    {
        return [
            "Id" => $action_id,
            "TransactionId" => $transaction_id,
            "WagerId" => $wager_id,
            "ResponseCode" => self::STATUS_CURRENCY_MISMATCH,
            "ResponseMessage" => self::STATUS_MESSAGE_CURRENCY_MISMATCH,
        ];
    }

    private static function actionUserNotFoundResponse($action_id, $transaction_id, $wager_id)
    {
        return [
            "Id" => $action_id,
            "TransactionId" => $transaction_id,
            "WagerId" => $wager_id,
            "ResponseCode" => self::STATUS_ACCOUNT_NOT_FOUND,
            "ResponseMessage" => self::STATUS_MESSAGE_ACCOUNT_NOT_FOUND,
        ];
    }

    private static function insufficientFundsResponse($action_id, $transaction_id, $wager_id)
    {
        return [
            "Id" => $action_id,
            "TransactionId" => $transaction_id,
            "WagerId" => $wager_id,
            "ResponseCode" => self::STATUS_INSUFFICIENT_FUNDS,
            "ResponseMessage" => self::STATUS_MESSAGE_INSUFFICIENT_FUNDS,
        ];
    }

    private static function transactionActionNotSupportedResponse($action_id, $transaction_id, $wager_id)
    {
        return [
            "Id" => $action_id,
            "TransactionId" => $transaction_id,
            "WagerId" => $wager_id,
            "ResponseCode" => self::STATUS_UNKNOWN_ERROR,
            "ResponseMessage" => self::STATUS_MESSAGE_TRANSACTION_ACTION_NOT_SUPPORTER,
        ];
    }

    private static function userMismatch($action_id, $transaction_id, $wager_id)
    {
        return [
            "Id" => $action_id,
            "TransactionId" => $transaction_id,
            "WagerId" => $wager_id,
            "ResponseCode" => self::STATUS_ACCOUNT_NOT_FOUND,
            "ResponseMessage" => self::STATUS_MESSAGE_ACCOUNT_NOT_FOUND,
        ];
    }

    private static function duplicatedTransactionResponse($action_id, $transaction_id, $wager_id)
    {
        return [
            "Id" => $action_id,
            "TransactionId" => $transaction_id,
            "WagerId" => $wager_id,
            "ResponseCode" => self::STATUS_UNKNOWN_ERROR,
            "ResponseMessage" => self::STATUS_MESSAGE_DUPLICATED_TRANSACTION,
        ];
    }

    private static function balanceSuccessResponse($user_id, $balance)
    {
        return [
            'Result' => [
                'UserCode' => $user_id,
                'AvailableBalance' => self::roundBalance($balance),
            ],
            'ErrorCode' => self::STATUS_SUCCESS,
            'Timestamp' => now()->setTimezone('Etc/GMT+4')->toDateTimeString(),
        ];
    }

    private static function pingResponse()
    {
        return [
            'Result' => [
                'Available' => true,
            ],
            'ErrorCode' => self::STATUS_SUCCESS,
            'Timestamp' => now()->setTimezone('Etc/GMT+4')->toDateTimeString(),
        ];
    }

    private static function currencyMismatchResponse()
    {
        return [
            'Result' => [],
            'ErrorCode' => self::STATUS_CURRENCY_MISMATCH,
            'ErrorMessage' => self::STATUS_MESSAGE_CURRENCY_MISMATCH,
            'Timestamp' => now()->setTimezone('Etc/GMT+4')->toDateTimeString(),
        ];
    }


    private static function userNotFoundResponse()
    {
        return [
            'Result' => [],
            'ErrorCode' => self::STATUS_ACCOUNT_NOT_FOUND,
            'ErrorMessage' => self::STATUS_MESSAGE_ACCOUNT_NOT_FOUND,
            'Timestamp' => now()->setTimezone('Etc/GMT+4')->toDateTimeString(),
        ];
    }

    private static function actionNotSupportedResponse()
    {
        return [
            'Result' => [],
            'ErrorCode' => self::STATUS_UNKNOWN_ERROR,
            'ErrorMessage' => self::STATUS_MESSAGE_ACTION_NOT_SUPPORTER,
            'Timestamp' => now()->setTimezone('Etc/GMT+4')->toDateTimeString(),
        ];
    }

    private static function gameItemNotFoundResponse()
    {
        return [
            'Result' => [],
            'ErrorCode' => self::STATUS_UNKNOWN_ERROR,
            'ErrorMessage' => self::STATUS_MESSAGE_GAME_NOT_FOUND,
            'Timestamp' => now()->setTimezone('Etc/GMT+4')->toDateTimeString(),
        ];
    }

    private static function authFailedResponse()
    {
        return [
            'Result' => [],
            'ErrorCode' => self::STATUS_AUTH_FAILED,
            'ErrorMessage' => self::STATUS_MESSAGE_AUTH_FAILED,
            'Timestamp' => now()->setTimezone('Etc/GMT+4')->toDateTimeString(),
        ];
    }

    private static function ipNotAllowedResponse($ip)
    {
        return [
            'Result' => [],
            'ErrorCode' => self::STATUS_AUTH_FAILED,
            'ErrorMessage' => self::STATUS_MESSAGE_IP_WHITELIST,
            'Timestamp' => now()->setTimezone('Etc/GMT+4')->toDateTimeString(),
        ];
    }

    private static function unknownErrorResponse()
    {
        return [
            'Result' => [],
            'ErrorCode' => self::STATUS_UNKNOWN_ERROR,
            'ErrorMessage' => self::STATUS_MESSAGE_UNKNOWN_ERROR,
            'Timestamp' => now()->setTimezone('Etc/GMT+4')->toDateTimeString(),
        ];
    }

    private static function validationErrorResponse($error)
    {
        return [
            'Result' => [],
            'ErrorCode' => self::STATUS_UNKNOWN_ERROR,
            'ErrorMessage' => self::STATUS_MESSAGE_VALIDATION_ERROR,
            'error' => $error,
            'Timestamp' => now()->setTimezone('Etc/GMT+4')->toDateTimeString(),
        ];
    }
}
