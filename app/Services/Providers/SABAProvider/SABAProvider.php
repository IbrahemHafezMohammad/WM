<?php

namespace App\Services\Providers\SABAProvider;

use App\Jobs\SABACheckTicketStatus;
use Exception;
use Carbon\Carbon;
use App\Models\Bet;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Models\Player;
use GuzzleHttp\Client;
use App\Models\BetRound;
use Illuminate\Support\Str;
use App\Constants\BetConstants;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Log;
use App\Models\PlayerBalanceHistory;
use Illuminate\Support\Facades\Http;
use App\Models\GameTransactionHistory;
use Illuminate\Support\Facades\Config;
use App\Constants\GamePlatformConstants;
use App\Services\Providers\ProviderInterface;
use App\Constants\SABAProviderConfigConstants;
use App\Constants\PlayerBalanceHistoryConstants;
use App\Constants\GameTransactionHistoryConstants;
use App\Services\Providers\SABAProvider\DTOs\SABAConfigDTO;
use App\Services\Providers\SABAProvider\Enums\SABAActionEnums;
use App\Services\Providers\SABAProvider\Enums\SABACurrencyEnums;
use App\Services\Providers\SABAProvider\DTOs\SABASeamlessResponseDTO;

class SABAProvider implements ProviderInterface
{
    const ERROR_CODE_CURRENCY_NOT_SUPPORTED = 1;

    // status description
    const STATUS_DESCRIPTION_SUCCESS = "Success";
    const STATUS_DESCRIPTION_INVALID_USER_ID = "Account does not exist";
    const STATUS_DESCRIPTION_INVALID_CURRENCY = "Invalid Currency";
    const STATUS_DESCRIPTION_INVALID_IP_ADDRESS = "Invalid IP Address";
    const STATUS_DESCRIPTION_AUTH_FAILED = "Invalid Authentication Key";
    const STATUS_DESCRIPTION_UNKNOWN_ERROR = "System Error";
    const STATUS_DESCRIPTION_INVALID_PARAMETER = "Parameter(s) Incorrect";
    const STATUS_DESCRIPTION_UNKNOWN_ACTION = "Unknown Action";
    const STATUS_DESCRIPTION_DUPLICATED_TRANSACTION = "Duplicated Transaction";
    const STATUS_DESCRIPTION_INSUFFICIENT_BALANCE = "Player Has Insufficient Funds";
    const STATUS_DESCRIPTION_TRANSACTION_NOT_FOUND = "Transaction Not Found";

    // status codes
    const STATUS_CODE_SUCCESS = "0";
    const STATUS_CODE_INVALID_USER_ID = "203";
    const STATUS_CODE_INVALID_CURRENCY = "302";
    const STATUS_CODE_INVALID_IP_ADDRESS = "312";
    const STATUS_CODE_AUTH_FAILED = "311";
    const STATUS_CODE_UNKNOWN_ERROR = "999";
    const STATUS_CODE_INVALID_PARAMETER = "101";
    const STATUS_CODE_UNDEFINED_ERROR = "-1";
    const STATUS_CODE_DUPLICATED_TRANSACTION = "1";
    const STATUS_CODE_INSUFFICIENT_BALANCE = "502";
    const STATUS_CODE_NO_SUCH_TICKET = "504";

    // odds type
    const ODDS_TYPE_MALAY = 1;
    const ODDS_TYPE_CHINA = 2;
    const ODDS_TYPE_DECIMAL = 3;
    const ODDS_TYPE_INDO = 4;
    const ODDS_TYPE_AMERICAN = 5;
    const ODDS_TYPE_MYANMAR = 6;

    // transaction statuses
    const TRANSACTION_STATUS_SUCCESS = 0;
    const TRANSACTION_STATUS_FAILED = 1;
    const TRANSACTION_STATUS_PENDING = 2;

    // bet limits
    const TRANSFER_LIMIT_MAX = 9999999999;
    const TRANSFER_LIMIT_MIN = 1;

    //reference separator
    const REFERENCE_SEPARATOR = '~~';

    // language
    const LANGUAGE_VIETNAMESE = 'vn';
    const LANGUAGE_ENGLISH = 'en';
    const LANGUAGE_HINDI = 'hi';


    protected $username;
    protected $first_name;
    protected $vendor_id;
    protected $operator_id;
    protected $seamless_secret;
    protected $base_url;
    protected $headers;
    protected $oddstype;
    protected $language;
    protected $currency;
    protected $max_transfer_limit;
    protected $min_transfer_limit;

    function __construct(Player $player)
    {
        $this->first_name = $player->user->user_name;
        $saba_currency = self::getGameCurrency($player->wallet->currency);
        $credentials = self::getCredential($saba_currency);
        $this->operator_id = $credentials['operator_id'];
        $this->vendor_id = $credentials['vendor_id'];
        $this->base_url = $credentials['base_url'];
        $this->seamless_secret = $credentials['seamless_secret'];
        $this->headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        $config = self::getConfig($player);
        $saba_language = self::getGameLanguage($player->language);
        $this->username = $this->operator_id . '_' . $config?->user_name;
        $this->oddstype = $config?->odds_type;
        $this->language = $saba_language;
        $this->currency = $saba_currency?->value;
        $this->max_transfer_limit = $config?->max_transfer_limit;
        $this->min_transfer_limit = $config?->min_transfer_limit;
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        $deviceType == 'Mobile' ? $deviceType = 2 : $deviceType = 1;

        if (is_null($this->currency)) {

            $result = [
                'error' => self::ERROR_CODE_CURRENCY_NOT_SUPPORTED,
            ];

            return json_encode($result);
        }

        $data = [
            'vendor_id' => $this->vendor_id,
            'vendor_member_id' => $this->username,
            'platform' => $deviceType,
        ];

        try {

            $client = new Client();

            $response = $client->post($this->base_url . '/api/GetSabaUrl', [
                'form_params' => $data,
                'headers' => $this->headers
            ]);

            $result = json_decode($response->getBody()->getContents());

            if ($result->error_code == self::STATUS_CODE_SUCCESS) {

                $result->Data = getUrl($result->Data, [
                    'lang' => $this->language,
                    'OTyp' => $this->oddstype,
                ]);
            }

            return json_encode($result);

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('SABA Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        if (is_null($this->currency)) {

            $result = [
                'error' => self::ERROR_CODE_CURRENCY_NOT_SUPPORTED,
            ];

            return json_encode($result);
        }

        $data = [
            'vendor_id' => $this->vendor_id,
            'vendor_member_id' => $this->username,
            'operatorId' => $this->operator_id,
            'username' => $this->username,
            'oddstype' => $this->oddstype,
            'currency' => $this->currency,
            'maxtransfer' => $this->max_transfer_limit,
            'mintransfer' => $this->min_transfer_limit,
            'firstname' => $this->first_name,
        ];

        try {

            $client = new Client();

            $response = $client->post($this->base_url . '/api/CreateMember', [
                'form_params' => $data,
                'headers' => $this->headers,
            ]);

            return $response->getBody()->getContents();

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('SABA Provider Call registerToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }

    }

    public function updateMember($first_name, $last_name, $oddstype, $max_transfer_limit, $min_transfer_limit): ?string
    {
        if (is_null($this->currency)) {

            $result = [
                'error' => self::ERROR_CODE_CURRENCY_NOT_SUPPORTED,
            ];

            return json_encode($result);
        }

        $data = [
            'vendor_id' => $this->vendor_id,
            'vendor_member_id' => $this->username,
            'firstname' => $first_name,
            'lastname' => $last_name,
            'oddstype' => $oddstype,
            'maxtransfer' => $max_transfer_limit,
            'mintransfer' => $min_transfer_limit,
        ];

        try {

            $client = new Client();

            $response = $client->post($this->base_url . '/api/UpdateMember', [
                'form_params' => $data,
                'headers' => $this->headers,
            ]);

            return $response->getBody()->getContents();

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('SABA Provider Call updateMember API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }

    }

    public function checkTicketStatus($refId, $txId): ?string
    {
        if (is_null($this->currency)) {

            $result = [
                'error' => self::ERROR_CODE_CURRENCY_NOT_SUPPORTED,
            ];

            return json_encode($result);
        }

        $data = [
            'vendor_id' => $this->vendor_id,
            'refId' => $refId,
            'txId' => $txId,
        ];

        try {

            $client = new Client();

            $response = $client->post($this->base_url . '/api/checkticketstatus', [
                'form_params' => $data,
                'headers' => $this->headers,
            ]);

            return $response->getBody()->getContents();

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('SABA Provider Call checkTicketStatus API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }

    }

    public static function getConfig(player $player): ?SABAConfigDTO
    {
        return match ($player->wallet->currency) {
            GlobalConstants::CURRENCY_VNDK => new SABAConfigDTO($player->user->user_name, self::ODDS_TYPE_CHINA, self::TRANSFER_LIMIT_MAX, self::TRANSFER_LIMIT_MIN),
            GlobalConstants::CURRENCY_INR => new SABAConfigDTO($player->user->user_name, self::ODDS_TYPE_CHINA, self::TRANSFER_LIMIT_MAX, self::TRANSFER_LIMIT_MIN),
            default => null
        };
    }

    public static function getCredential(SABACurrencyEnums $saba_currency)
    {
        $vendor_id = null;
        $operator_id = null;

        switch ($saba_currency) {
            case SABACurrencyEnums::VNDK:
                $vendor_id = Config::get('app.saba_vendor_id.vndk');
                $operator_id = Config::get('app.saba_operator_id.vndk');
                break;
            case SABACurrencyEnums::INR:
                $vendor_id = Config::get('app.saba_vendor_id.inr');
                $operator_id = Config::get('app.saba_operator_id.inr');
                break;
        }

        return [
            'vendor_id' => $vendor_id,
            'operator_id' => $operator_id,
            'base_url' => Config::get('app.saba_base_url'),
            'seamless_secret' => Config::get('app.saba_seamless_secret'),
        ];
    }

    public static function getGameCurrency($currency): ?SABACurrencyEnums
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => SABACurrencyEnums::VNDK,
            GlobalConstants::CURRENCY_INR => SABACurrencyEnums::INR,
            default => null
        };
    }

    public static function RoundBalance($balance)
    {
        return round($balance, 5);
    }

    public static function getGameLanguage($language)
    {
        return match ($language) {
            GlobalConstants::LANG_VN => self::LANGUAGE_VIETNAMESE,
            GlobalConstants::LANG_EN => self::LANGUAGE_ENGLISH,
            GlobalConstants::LANG_HI => self::LANGUAGE_HINDI,
            default => self::LANGUAGE_ENGLISH
        };
    }

    public static function generateReference($reference, $suffix)
    {
        $separator = self::REFERENCE_SEPARATOR;
        return $reference . $separator . $suffix;
    }

    public static function parseReference($reference)
    {
        $separator = self::REFERENCE_SEPARATOR;
        $parts = explode($separator, $reference);

        if (count($parts) === 2) {
            return [
                'reference' => $parts[0],
                'suffix' => $parts[1],
            ];
        }

        return [
            'reference' => null,
            'suffix' => null,
        ];

        // return null;
    }

    public static function authorizeProvider($seamless_secret, SABACurrencyEnums $saba_currency)
    {
        $credentials = self::getCredential($saba_currency);

        return ($seamless_secret === $credentials['seamless_secret']);
    }

    // seamless handling
    public static function ipNotAllowed($ip): SABASeamlessResponseDTO
    {
        return new SABASeamlessResponseDTO(self::ipNotAllowedResponse($ip), 200);
    }

    public static function authFailed(): SABASeamlessResponseDTO
    {
        return new SABASeamlessResponseDTO(self::authFailedResponse(), 200);
    }

    public static function unknownError(): SABASeamlessResponseDTO
    {
        return new SABASeamlessResponseDTO(self::unknownErrorResponse(), 200);
    }

    public static function validationError($error): SABASeamlessResponseDTO
    {
        return new SABASeamlessResponseDTO(self::validationErrorResponse($error), 200);
    }

    public static function walletAccess($game_item, $data, $requested_currency): SABASeamlessResponseDTO
    {
        return match ($data['action']) {
            SABAActionEnums::GET_BALANCE->value => self::getBalance($data, $requested_currency),
            SABAActionEnums::PLACE_BET->value => self::placeBet($game_item, $data, $requested_currency),
            SABAActionEnums::CONFIRM_BET->value => self::confirmBet($game_item, $data, $requested_currency),
            SABAActionEnums::CANCEL_BET->value => self::cancelBet($game_item, $data, $requested_currency),
            default => self::unknownError(),
        };
    }

    private static function getBalance($data, $requested_currency): SABASeamlessResponseDTO
    {
        $user = User::where('user_name', $data['userId'])->first();

        if (!$user) {
            return new SABASeamlessResponseDTO(self::userNotFoundResponse(), 200);
        }

        $player = $user->player;
        $locked_wallet = $player->wallet()->lockForUpdate()->first();
        $balance = $locked_wallet->balance;

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if ($player_game_currency != $requested_currency) {
            return new SABASeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
        }

        return new SABASeamlessResponseDTO(self::getBalanceSuccessResponse($data['userId'], $balance), 200);
    }

    private static function placeBet($game_item, $data, $requested_currency): SABASeamlessResponseDTO
    {
        $user = User::where('user_name', $data['userId'])->first();

        if (!$user) {
            return new SABASeamlessResponseDTO(self::userNotFoundResponse(), 200);
        }

        $player = $user->player;
        $locked_wallet = $player->wallet()->lockForUpdate()->first();
        $balance = $locked_wallet->balance;

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        $bet_currency = SABACurrencyEnums::tryFrom($data['currency']);

        if (($player_game_currency != $requested_currency) || ($player_game_currency != $bet_currency)) {
            return new SABASeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
        }

        $dup_transaction = $game_item->gameTransactionHistories()->transactionRequestNo($data['operationId'])->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

        if ($dup_transaction) {
            return new SABASeamlessResponseDTO(self::duplicateTransactionResponse(), 200);
        }

        $game_transaction_history = GameTransactionHistory::gameAction(
            $balance,
            $player->id,
            $locked_wallet->currency,
            $locked_wallet->id,
            $game_item->id,
            $data['operationId'],
            $data['operationId'],
            $game_item->gamePlatform->id,
        );

        $player_balance_history = PlayerBalanceHistory::gameAction(
            $player->id,
            $balance,
            $locked_wallet->currency,
        );

        $transfer_no = Uuid::uuid4()->toString();

        if ($locked_wallet->balance < $data['debitAmount']) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $data['debitAmount'],
                true,
                self::STATUS_CODE_INSUFFICIENT_BALANCE,
                $transfer_no,
            );

            return new SABASeamlessResponseDTO(self::insufficientBalanceResponse(), 200);
        }

        // $locked_wallet->debit($data['debitAmount']);

        $bet_round = BetRound::begin(
            $player->id,
            $game_item->gamePlatform->id,
            $data['refId'],
            Carbon::parse($data['betTime'])->setTimezone('UTC')->toDateTimeString(),
            $locked_wallet->currency,
            null,
            $data['betFrom'],
            $data['IP'],
            Carbon::parse($data['betTime'])->setTimezone('UTC')->toDateTimeString()
        );
        
        $bet = Bet::place(
            $data['betAmount'],
            $bet_round->round_reference,
            $data['refId'],
            $bet_round->id,
            $game_item->id,
            Carbon::parse($data['betTime'])->setTimezone('UTC')->toDateTimeString(),
            $locked_wallet->currency,
            $data['odds'],
        );

        $game_transaction_history->gameActionPending(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
            $data['debitAmount'],
            true,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET,
            $transfer_no,
            $bet->id,
        );

        $player_balance_history->gameActionPending(
            $data['debitAmount'],
            true,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET
        );

        SABACheckTicketStatus::dispatch($data['refId'], $transfer_no, $game_item, $player)->delay(now()->addMinutes(10));

        return new SABASeamlessResponseDTO(self::placeBetSuccessResponse($data['refId'], $transfer_no), 200);
    }

    private static function confirmBet($game_item, $data, $requested_currency): SABASeamlessResponseDTO
    {
        $user = User::where('user_name', $data['userId'])->first();

        if (!$user) {
            return new SABASeamlessResponseDTO(self::userNotFoundResponse(), 200);
        }

        $player = $user->player;
        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if (($player_game_currency != $requested_currency)) {
            return new SABASeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
        }

        $dup_transaction = $game_item->gameTransactionHistories()->transactionRequestNo($data['operationId'])->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

        if ($dup_transaction) {
            return new SABASeamlessResponseDTO(self::duplicateTransactionResponse(), 200);
        }

        $bets = $data['txns'];

        $transaction_references = collect($bets)->pluck('licenseeTxId')->unique();

        $transactions = $game_item->gameTransactionHistories()->gameTransactionNo($transaction_references)->status(GameTransactionHistoryConstants::STATUS_PENDING)->get()->keyBy('game_transaction_no');

        foreach ($bets as $bet) {

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $bet['txId'],
                $data['operationId'],
                $game_item->gamePlatform->id,
            );

            $transaction = $transactions->get($bet['licenseeTxId']);

            $player_bet = $transaction?->bet;

            if (!$transaction || !$player_bet || $player_bet->bet_reference != $bet['refId']) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    $bet['debitAmount'],
                    true,
                    self::STATUS_DESCRIPTION_TRANSACTION_NOT_FOUND,
                );

                return new SABASeamlessResponseDTO(self::transactionNotFoundResponse(), 200);
            }
        }

        foreach ($bets as $bet) {

            $transaction = $transactions->get($bet['licenseeTxId']);

            $player_bet = $transaction->bet;

            $locked_wallet->debit($transaction->points);

            $transaction->setStatus(GameTransactionHistoryConstants::STATUS_SUCCESS);

            $transaction->playerBalanceHistory->setStatus(PlayerBalanceHistoryConstants::STATUS_SUCCESS);

            if ($bet['isOddsChanged']) {

                $game_transaction_history = GameTransactionHistory::gameAction(
                    $locked_wallet->balance,
                    $player->id,
                    $locked_wallet->currency,
                    $locked_wallet->id,
                    $game_item->id,
                    $bet['txId'],
                    $data['operationId'],
                    $game_item->gamePlatform->id,
                );

                $player_balance_history = PlayerBalanceHistory::gameAction(
                    $player->id,
                    $locked_wallet->balance,
                    $locked_wallet->currency,
                );

                $transaction->points > $bet['actualAmount'] ? $is_withdraw = false : $is_withdraw = true;

                $adjustment_amount = abs($transaction->points - $bet['actualAmount']);

                $is_withdraw ? $locked_wallet->debit($adjustment_amount) : $locked_wallet->credit($adjustment_amount);

                $player_bet->adjust(null, $bet['actualAmount'], $bet['actualAmount'], $bet['odds']);

                $game_transaction_history->gameActionSuccess(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ADJUST,
                    $adjustment_amount,
                    $is_withdraw,
                    $locked_wallet->balance,
                    GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION,
                    null,
                    $player_bet->id,
                    $transaction->id,
                );

                $player_balance_history->gameActionSuccess(
                    $adjustment_amount,
                    $is_withdraw,
                    $locked_wallet->balance,
                    $game_transaction_history->id,
                    GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION
                );
            }
        }

        return new SABASeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function cancelBet($game_item, $data, $requested_currency): SABASeamlessResponseDTO
    {
        $user = User::where('user_name', $data['userId'])->first();

        if (!$user) {
            return new SABASeamlessResponseDTO(self::userNotFoundResponse(), 200);
        }

        $player = $user->player;
        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if (($player_game_currency != $requested_currency)) {
            return new SABASeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
        }

        $bets = $data['txns'];

        $bets_references = collect($bets)->pluck('refId')->unique();

        $player_bets = $game_item->bets()->referenceIn($bets_references)->status(BetConstants::STATUS_UNSETTLED)->get()->keyBy('bet_reference');

        foreach ($bets as $bet) {

            $player_bet = $player_bets->get($bet['refId']);

            if (!$player_bet) {

                continue;
            }

            $transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $player_bet->cancel(now()->toDateTimeString());

            $player_bet->betRound->close(now()->toDateTimeString(), null);

            $transaction->setStatus(GameTransactionHistoryConstants::STATUS_FAILURE);

            $transaction->playerBalanceHistory->setStatus(PlayerBalanceHistoryConstants::STATUS_FAILURE);
        }

        return new SABASeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function settleBet($game_item, $data, $requested_currency): SABASeamlessResponseDTO
    {
        $dup_transaction = $game_item->gameTransactionHistories()->gameTransactionNo($data['operationId'])->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

        if ($dup_transaction) {
            return new SABASeamlessResponseDTO(self::duplicateTransactionResponse(), 200);
        }

        $bets = $data['txns'];

        $bets_references = collect($bets)->pluck('refId')->unique();

        $player_bets = $game_item->bets()->referenceIn($bets_references)->status(BetConstants::STATUS_UNSETTLED)->get()->keyBy('bet_reference');

        $user_names = collect($bets)->pluck('userId')->unique();

        $users = User::whereIn('user_name', $user_names)->get()->keyBy('user_name');

        foreach ($bets as $bet) {

            $user = $users->get($bet['userId']);

            if (!$user) {
                return new SABASeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {
                return new SABASeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            $player_bet = $player_bets->get($bet['refId']);

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $bet['txId'],
                $data['operationId'],
                $game_item->gamePlatform->id,
            );

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    $bet['creditAmount'],
                    false,
                    self::STATUS_DESCRIPTION_TRANSACTION_NOT_FOUND,
                );

                return new SABASeamlessResponseDTO(self::transactionNotFoundResponse(), 200);
            }
        }

        foreach ($bets as $bet) {

            $user = $users->get($bet['userId']);

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_bet = $player_bets->get($bet['refId']);

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $bet['txId'],
                $data['operationId'],
                $game_item->gamePlatform->id,
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $locked_wallet->credit($bet['creditAmount']);

            $player_bet->settle($bet['creditAmount'], Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString());

            $win_loss = $bet['creditAmount'] - $player_bet->valid_bet;

            $player_bet->betRound->close(Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString(), $win_loss);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                $bet['creditAmount'],
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $player_balance_history->gameActionSuccess(
                $bet['winAmount'],
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION
            );
        }

        return new SABASeamlessResponseDTO(self::successResponse(null), 200);
    }

    private static function resettleBet($game_item, $data, $requested_currency): SABASeamlessResponseDTO
    {
        $dup_transaction = $game_item->gameTransactionHistories()->gameTransactionNo($data['operationId'])->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

        if ($dup_transaction) {
            return new SABASeamlessResponseDTO(self::duplicateTransactionResponse(), 200);
        }

        $bets = $data['txns'];

        $bets_references = collect($bets)->pluck('refId')->unique();

        $player_bets = $game_item->bets()->referenceIn($bets_references)->status(BetConstants::STATUS_SETTLED)->get()->keyBy('bet_reference');

        $user_names = collect($bets)->pluck('userId')->unique();

        $users = User::whereIn('user_name', $user_names)->get()->keyBy('user_name');

        foreach ($bets as $bet) {

            $user = $users->get($bet['userId']);

            if (!$user) {
                return new SABASeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {
                return new SABASeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            $player_bet = $player_bets->get($bet['refId']);

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $bet['txId'],
                $data['operationId'],
                $game_item->gamePlatform->id,
            );

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    $bet['creditAmount'],
                    false,
                    self::STATUS_DESCRIPTION_TRANSACTION_NOT_FOUND,
                );

                return new SABASeamlessResponseDTO(self::transactionNotFoundResponse(), 200);
            }
        }

        foreach ($bets as $bet) {

            $user = $users->get($bet['userId']);

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_bet = $player_bets->get($bet['refId']);

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $bet['txId'],
                $data['operationId'],
                $game_item->gamePlatform->id,
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            if ($bet['isOnlyWinlostDateChanged']) {

                $player_bet_round = $player_bet->betRound;

                $player_bet_round->reclose(Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString(), $player_bet_round->win_loss);

            } else {

                $refer_transaction->points > $bet['creditAmount'] ? $is_withdraw = true : $is_withdraw = false;

                $adjustment_amount = abs($refer_transaction->points - $bet['creditAmount']);

                $is_withdraw ? $locked_wallet->debit($adjustment_amount) : $locked_wallet->credit($adjustment_amount);

                $player_bet->resettle(
                    $bet['creditAmount'],
                    Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString(),
                    $player_bet->bet_on,
                    $player_bet->rebate,
                    $player_bet->comm,
                    $player_bet->valid_bet,
                    $player_bet->turnover,
                    $player_bet->odds,
                );

                $win_loss = $player_bet->win_amount - $player_bet->valid_bet;

                $player_bet->betRound->reclose(Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString(), $win_loss);

                $is_withdraw ? $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT : $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT;

                $game_transaction_history->gameActionSuccess(
                    $type,
                    $adjustment_amount,
                    $is_withdraw,
                    $locked_wallet->balance,
                    GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION,
                    null,
                    $player_bet->id,
                    $refer_transaction->id,
                );

                $player_balance_history->gameActionSuccess(
                    $adjustment_amount,
                    $is_withdraw,
                    $locked_wallet->balance,
                    $game_transaction_history->id,
                    GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION
                );
            }
        }

        return new SABASeamlessResponseDTO(self::successResponse(null), 200);
    }

    private static function unsettleBet($game_item, $data, $requested_currency): SABASeamlessResponseDTO
    {
        $dup_transaction = $game_item->gameTransactionHistories()->gameTransactionNo($data['operationId'])->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

        if ($dup_transaction) {
            return new SABASeamlessResponseDTO(self::duplicateTransactionResponse(), 200);
        }

        $bets = $data['txns'];

        $bets_references = collect($bets)->pluck('refId')->unique();

        $player_bets = $game_item->bets()->referenceIn($bets_references)->scopeStatusIn([BetConstants::STATUS_SETTLED, BetConstants::STATUS_RESETTLED])->get()->keyBy('bet_reference');

        $user_names = collect($bets)->pluck('userId')->unique();

        $users = User::whereIn('user_name', $user_names)->get()->keyBy('user_name');

        foreach ($bets as $bet) {

            $user = $users->get($bet['userId']);

            if (!$user) {
                return new SABASeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {
                return new SABASeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            $player_bet = $player_bets->get($bet['refId']);

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $bet['txId'],
                $data['operationId'],
                $game_item->gamePlatform->id,
            );

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT,
                    $bet['debitAmount'],
                    true,
                    self::STATUS_DESCRIPTION_TRANSACTION_NOT_FOUND,
                );

                return new SABASeamlessResponseDTO(self::transactionNotFoundResponse(), 200);
            }
        }

        foreach ($bets as $bet) {

            $user = $users->get($bet['userId']);

            $player = $user->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_bet = $player_bets->get($bet['refId']);

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $bet['txId'],
                $data['operationId'],
                $game_item->gamePlatform->id,
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $locked_wallet->debit($bet['debitAmount']);

            $player_bet->unsettle();

            $player_bet->betRound->reopen();

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $bet['debitAmount'],
                true,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_TYPE_UNSETTLE_BET,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $player_balance_history->gameActionSuccess(
                $bet['debitAmount'],
                true,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION
            );

        }

        return new SABASeamlessResponseDTO(self::successResponse(null), 200);
    }

    // private static function placeBetParlay
    // adjustBalance, PlaceBetParlay, ConfirmBetParlay
    private static function successResponse($balance)
    {
        return [
            'status' => self::STATUS_CODE_SUCCESS,
            'balance' => $balance,
            'msg' => null,
        ];
    }

    private static function transactionNotFoundResponse()
    {
        return [
            'status' => self::STATUS_CODE_NO_SUCH_TICKET,
            'msg' => self::STATUS_DESCRIPTION_TRANSACTION_NOT_FOUND,
        ];
    }

    private static function placeBetSuccessResponse($refId, $transaction_no)
    {
        return [
            'status' => self::STATUS_CODE_SUCCESS,
            'refId' => $refId,
            'licenseeTxId' => $transaction_no,
            'msg' => null,
        ];
    }

    private static function insufficientBalanceResponse()
    {
        return [
            'status' => self::STATUS_CODE_INSUFFICIENT_BALANCE,
            'msg' => self::STATUS_DESCRIPTION_INSUFFICIENT_BALANCE,
        ];
    }

    private static function duplicateTransactionResponse()
    {
        return [
            'status' => self::STATUS_CODE_DUPLICATED_TRANSACTION,
            'msg' => self::STATUS_DESCRIPTION_DUPLICATED_TRANSACTION,
        ];
    }

    private static function undefinedActionResponse()
    {
        return [
            'status' => self::STATUS_CODE_UNDEFINED_ERROR,
            'msg' => self::STATUS_DESCRIPTION_UNKNOWN_ACTION,
        ];
    }

    private static function invalidCurrencyResponse()
    {
        return [
            'status' => self::STATUS_CODE_INVALID_CURRENCY,
            'msg' => self::STATUS_DESCRIPTION_INVALID_CURRENCY,
        ];
    }

    private static function userNotFoundResponse()
    {
        return [
            'status' => self::STATUS_CODE_INVALID_USER_ID,
            'msg' => self::STATUS_DESCRIPTION_INVALID_USER_ID,
        ];
    }

    private static function getBalanceSuccessResponse($user_id, $balance)
    {
        return [
            'status' => self::STATUS_CODE_SUCCESS,
            'userId' => $user_id,
            'balance' => self::RoundBalance($balance),
            'balanceTs' => now()->format('Y-m-d\TH:i:s.vP'),
            'msg' => null,
        ];
    }

    private static function ipNotAllowedResponse($ip)
    {
        return [
            'status' => self::STATUS_CODE_INVALID_IP_ADDRESS,
            'msg' => self::STATUS_DESCRIPTION_INVALID_IP_ADDRESS,
            'ip' => $ip,
        ];
    }

    private static function authFailedResponse()
    {
        return [
            'status' => self::STATUS_CODE_AUTH_FAILED,
            'msg' => self::STATUS_DESCRIPTION_AUTH_FAILED,
        ];
    }

    private static function unknownErrorResponse()
    {
        return [
            'status' => self::STATUS_CODE_UNKNOWN_ERROR,
            'msg' => self::STATUS_DESCRIPTION_UNKNOWN_ERROR,
        ];
    }

    private static function validationErrorResponse($error)
    {
        return [
            'status' => self::STATUS_CODE_INVALID_PARAMETER,
            'msg' => self::STATUS_DESCRIPTION_INVALID_PARAMETER,
            'parameter' => $error,
        ];
    }
}