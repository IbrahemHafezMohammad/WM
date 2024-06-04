<?php

namespace App\Services\Providers\ONEProvider;

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
use App\Services\Providers\ONEProvider\Enums\ONEActionsEnums;
use App\Services\Providers\ONEProvider\Enums\ONECurrencyEnums;
use App\Services\Providers\ONEProvider\DTOs\ONESeamlessResponseDTO;

class ONEProvider implements ProviderInterface
{
    // status code
    const STATUS_CODE_SUCCESS = 'SC_OK';
    const STATUS_CODE_UNKNOWN_ERROR = 'SC_UNKNOWN_ERROR';
    const STATUS_CODE_AUTH_FAILED = 'SC_INVALID_SIGNATURE';
    const STATUS_CODE_VALIDATION_FAILED = 'SC_INVALID_REQUEST';
    const STATUS_CODE_IP_WHITELIST = 'SC_AUTHENTICATION_FAILED';
    const STATUS_CODE_CURRENCY_MISMATCH = 'SC_WRONG_CURRENCY';
    const STATUS_CODE_DUPLICATED_REQUEST = 'SC_DUPLICATE_REQUEST';
    const STATUS_CODE_USER_NOT_FOUND = 'SC_USER_NOT_EXISTS';
    const STATUS_CODE_GAME_NOT_FOUND = 'SC_INVALID_GAME';
    const STATUS_CODE_INSUFFICIENT_FUNDS = 'SC_INSUFFICIENT_FUNDS';
    const STATUS_CODE_INVALID_BET = 'SC_TRANSACTION_NOT_EXISTS';

    // status description
    const STATUS_DESC_UNKNOWN_ERROR = 'Generic status code for unknown errors.';
    const STATUS_DESC_AUTH_FAILED = 'X-Signature verification failed.';
    const STATUS_DESC_VALIDATION_FAILED = 'Wrong/missing parameters sent in request body.';
    const STATUS_DESC_IP_WHITELIST = 'IP not allowed, please request for whitelist';
    const STATUS_DESC_CURRENCY_MISMATCH = 'Transaction\'s currency is different from user\'s wallet currency.';
    const STATUS_DESC_DUPLICATED_REQUEST = 'Duplicate request.';
    const STATUS_DESC_USER_NOT_FOUND = 'User does not exists in Operator\'s system';
    const STATUS_DESC_GAME_NOT_FOUND = 'Not a valid game.';
    const STATUS_DESC_INSUFFICIENT_FUNDS = 'User\'s wallet does not have enough funds.';
    const STATUS_DESC_INVALID_BET = 'Corresponding bet transaction cannot be found.';

    // bet result types
    const BET_RESULT_WIN = 'WIN';
    const BET_RESULT_LOSE = 'LOSE';
    const BET_RESULT_BET_WIN = 'BET_WIN';
    const BET_RESULT_BET_LOSE = 'BET_LOSE';
    const BET_RESULT_END = 'END';

    //languages 
    const LANG_EN = 'en';
    const LANG_VN = 'vi';
    const LANG_HI = 'hi';

    protected $username;

    protected $game_code;

    protected $currency;

    protected $api_key;

    protected $base_url;

    protected $external_url;

    protected $headers;

    protected $api_secret;

    protected $language;

    protected $sub_platform;

    function __construct(Player $player, $game_code, $sub_platform)
    {
        $this->currency = self::getGameCurrency($player->wallet->currency);
        $credentials = self::getCredential($this->currency);
        $this->api_key = $credentials['api_key'];
        $this->base_url = $credentials['base_url'];
        $this->api_secret = $credentials['api_secret'];
        $this->external_url = $credentials['external_url'];

        $this->username = $player->user->user_name;
        $this->game_code = $game_code;
        $this->sub_platform = $sub_platform;

        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $this->api_key,
        ];

        $this->language = self::getGameLanguage($player->language);
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        try {

            $deviceType == 'Mobile' ? $platform = 'H5' : $platform = 'WEB';

            if ($this->sub_platform == GamePlatformConstants::ONE_SUB_PROVIDER_WINFINITY && !(filter_var($loginIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))) {
                $loginIp = Config::get('app.philippines_ip');
            }
            
            $data = [
                'username' => $this->username,
                'traceId' => Uuid::uuid4()->toString(),
                'gameCode' => $this->game_code,
                'language' => $this->language,
                'platform' => $platform,
                'currency' => $this->currency->value,
                'lobbyUrl' => $this->external_url,
                'ipAddress' => $loginIp,
            ];

            $json_request_data = json_encode($data);

            $signature = hash_hmac('sha256', $json_request_data, $this->api_secret);

            $this->headers['X-Signature'] = $signature;

            $response = Http::withHeaders($this->headers)->post($this->base_url . '/game/url', $data);

            $result =  $response->body();

            Log::info("ONE PROVIDER DEBUG");
            Log::info(json_encode([
                'api_url' => $this->base_url . '/game/url',
                'request_body' => $data,
                'request_headers' => $this->headers,
                'response' => $response->json(),
            ]));
            return $result;
        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('ONE Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        throw new Exception('NOT_SUPPORTED');
    }

    public static function getGamesList($vendor, ONECurrencyEnums $currency): ?string
    {
        try {
            $credentials = self::getCredential($currency);

            $api_key = $credentials['api_key'];
            $api_secret = $credentials['api_secret'];
            $base_url = $credentials['base_url'];

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-API-Key' => $api_key,
            ];

            $data = [
                'traceId' => Uuid::uuid4()->toString(),
                'vendorCode' => $vendor,
                'pageNo' => 1
            ];

            $json_request_data = json_encode($data);

            $signature = hash_hmac('sha256', $json_request_data, $api_secret);

            $headers['X-Signature'] = $signature;

            $response = Http::withHeaders($headers)->post($base_url . '/game/list', $data);

            return $response->body();
        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('ONE Provider Call getGamesList API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public static function getCredential(ONECurrencyEnums $one_currency)
    {
        $api_key = null;
        $api_secret = null;
        $external_url = null;

        switch ($one_currency) {
            case ONECurrencyEnums::VNDK:
                $api_key = Config::get('app.one_api_key.vndk');
                $api_secret = Config::get('app.one_api_secret.vndk');
                $external_url = Config::get('app.one_external_url.vndk');
                break;
            case ONECurrencyEnums::PHP:
                $api_key = Config::get('app.one_api_key.php');
                $api_secret = Config::get('app.one_api_secret.php');
                $external_url = Config::get('app.one_external_url.php');
                break;
            case ONECurrencyEnums::INR:
                $api_key = Config::get('app.one_api_key.inr');
                $api_secret = Config::get('app.one_api_secret.inr');
                $external_url = Config::get('app.one_external_url.inr');
                break;
        }

        return [
            'base_url' => Config::get('app.one_base_url'),
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'external_url' => $external_url,
        ];
    }

    public static function getSystemCurrency(ONECurrencyEnums $currency): int
    {
        return match ($currency) {
            ONECurrencyEnums::VNDK => GlobalConstants::CURRENCY_VNDK,
            ONECurrencyEnums::PHP => GlobalConstants::CURRENCY_PHP,
            ONECurrencyEnums::INR => GlobalConstants::CURRENCY_INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getGameCurrency($currency): ONECurrencyEnums
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => ONECurrencyEnums::VNDK,
            GlobalConstants::CURRENCY_PHP => ONECurrencyEnums::PHP,
            GlobalConstants::CURRENCY_INR => ONECurrencyEnums::INR,
            default => throw new Exception('Unsupported Currency')
        };
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

    public static function roundBalance($balance)
    {
        return round($balance, 6);
    }

    public static function authorizeProvider($api_signature, $request_body, ONECurrencyEnums $one_currency)
    {
        $credentials = self::getCredential($one_currency);

        $signature = hash_hmac('sha256', $request_body, $credentials['api_secret']);

        return $api_signature === $signature;
    }

    public static function getGameLobby($game_code)
    {
        $one_providers = GamePlatformConstants::getONEProviderLobbies();

        foreach ($one_providers as $code => $lobby) {
            if (strpos($game_code, $code) === 0) {
                return $lobby;
            }
        }

        return null;
    }

    public static function getBetResultTypes()
    {
        return [
            self::BET_RESULT_WIN,
            self::BET_RESULT_LOSE,
            self::BET_RESULT_BET_WIN,
            self::BET_RESULT_BET_LOSE,
            self::BET_RESULT_END,
        ];
    }

    public static function unknownError($trace_id): ONESeamlessResponseDTO
    {
        return new ONESeamlessResponseDTO(self::unknownErrorResponse($trace_id), 200);
    }

    public static function authFailed($trace_id): ONESeamlessResponseDTO
    {
        return new ONESeamlessResponseDTO(self::authFailedResponse($trace_id), 200);
    }

    public static function validationError($error, $trace_id): ONESeamlessResponseDTO
    {
        return new ONESeamlessResponseDTO(self::validationErrorResponse($error, $trace_id), 200);
    }

    public static function ipNotAllowed($ip, $trace_id): ONESeamlessResponseDTO
    {
        return new ONESeamlessResponseDTO(self::ipNotAllowedResponse($ip, $trace_id), 200);
    }

    public static function walletAccess($request_data, $wallet_action, $requested_currency): ONESeamlessResponseDTO
    {
        $user = User::where('user_name', $request_data['username'])->first();

        if (!$user) {
            return new ONESeamlessResponseDTO(self::userNotFoundResponse($request_data['traceId']), 200);
        }

        $player = $user->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        $game_currency = ONECurrencyEnums::tryFrom($request_data['currency']);

        if ($player_game_currency !== $requested_currency || $player_game_currency !== $game_currency) {
            return new ONESeamlessResponseDTO(self::currencyMismatchResponse($request_data['traceId']), 200);
        }

        if ($wallet_action == ONEActionsEnums::BALANCE) {
            return new ONESeamlessResponseDTO(
                self::successResponse(
                    $request_data['traceId'],
                    $request_data['username'],
                    $request_data['currency'],
                    $locked_wallet->balance
                ),
                200
            );
        }

        $game_item = GameItem::where('game_id', $request_data['gameCode'])->first();

        $game_lobby = self::getGameLobby($request_data['gameCode']);

        $game_item = $game_item ?? GameItem::where('game_id', $game_lobby)->first();

        if (!$game_item) {

            return new ONESeamlessResponseDTO(self::gameItemNotFoundResponse($request_data['traceId']), 200);
        }

        $is_dup_transaction = GameTransactionHistory::referenceNo($request_data['transactionId'])
            ->gamePlatformId($game_item->gamePlatform->id)
            ->playerId($player->id)
            ->status(GameTransactionHistoryConstants::STATUS_SUCCESS)
            ->first();

        if ($is_dup_transaction) {

            return new ONESeamlessResponseDTO(self::successResponse(
                $request_data['traceId'],
                $request_data['username'],
                $request_data['currency'],
                $locked_wallet->balance
            ), 200);
        }

        $game_transaction_history = GameTransactionHistory::gameAction(
            $locked_wallet->balance,
            $player->id,
            $locked_wallet->currency,
            $locked_wallet->id,
            $game_item->id,
            $request_data['transactionId'],
            $request_data['traceId'],
            $game_item->gamePlatform->id
        );

        $player_balance_history = PlayerBalanceHistory::gameAction(
            $player->id,
            $locked_wallet->balance,
            $locked_wallet->currency,
        );

        return match ($wallet_action) {
            ONEActionsEnums::BET => self::placeBet(
                $request_data,
                $player,
                $game_item,
                $locked_wallet,
                $game_transaction_history,
                $player_balance_history,
            ),
            ONEActionsEnums::BET_RESULT => self::betResult(
                $request_data,
                $player,
                $game_item,
                $locked_wallet,
                $game_transaction_history,
                $player_balance_history,
            ),
            ONEActionsEnums::ROLLBACK => self::rollBack(
                $request_data,
                $player,
                $game_item,
                $locked_wallet,
                $game_transaction_history,
                $player_balance_history,
            ),
            ONEActionsEnums::ADJUSTMENT => self::adjust(
                $request_data,
                $player,
                $game_item,
                $locked_wallet,
                $game_transaction_history,
                $player_balance_history,
            ),
        };
    }

    private static function adjust(
        $request_data,
        $player,
        $game_item,
        $locked_wallet,
        $game_transaction_history,
        $player_balance_history,
    ): ONESeamlessResponseDTO {

        $is_withdraw = $request_data['amount'] < 0;

        $amount = abs($request_data['amount']);

        if ($is_withdraw && $locked_wallet->balance < $amount) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ADJUST,
                $amount,
                true,
                self::STATUS_DESC_INSUFFICIENT_FUNDS,
            );

            return new ONESeamlessResponseDTO(self::inefficientFundsResponse($request_data['traceId']), 200);
        }

        $is_withdraw ? $locked_wallet->debit($amount) : $locked_wallet->credit($amount);

        $bet_round = BetRound::roundReference($request_data['roundId'])->gamePlatformId($game_item->gamePlatform->id)->playerId($player->id)->first();

        if (!$bet_round) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                0,
                false,
                self::STATUS_DESC_INVALID_BET,
            );

            return new ONESeamlessResponseDTO(self::invalidBetResponse($request_data['traceId']), 200);
        }

        $old_winloss = is_null($bet_round->win_loss) ? 0 : $bet_round->win_loss;

        $winloss = $is_withdraw ? $old_winloss - $amount : $old_winloss + $amount;

        $bet_round->adjust($winloss, $bet_round->ended_on);

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

        return new ONESeamlessResponseDTO(
            self::successResponse(
                $request_data['traceId'],
                $request_data['username'],
                $request_data['currency'],
                $locked_wallet->balance
            ),
            200
        );
    }

    private static function rollBack(
        $request_data,
        $player,
        $game_item,
        $locked_wallet,
        $game_transaction_history,
        $player_balance_history,
    ): ONESeamlessResponseDTO {

        $player_bet = $game_item->bets()
            ->statusIn([BetConstants::STATUS_SETTLED, BetConstants::STATUS_UNSETTLED])
            ->reference($request_data['betId'])
            ->playerId($locked_wallet->player_id)
            ->first();

        $timestamp = Carbon::createFromTimestampMs($request_data['timestamp']);

        if (!$player_bet || $player_bet?->betRound->round_reference !== $request_data['roundId']) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                0,
                false,
                self::STATUS_DESC_INVALID_BET,
            );

            return new ONESeamlessResponseDTO(self::invalidBetResponse($request_data['traceId']), 200);
        }

        $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

        if ($refer_transaction->transaction_type == GameTransactionHistoryConstants::TRANSACTION_TYPE_BET) {

            $locked_wallet->credit($refer_transaction->points);

            $player_bet->cancel(now()->toDateTimeString());

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
        }

        if ($refer_transaction->transaction_type == GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT || $refer_transaction->transaction_type == GameTransactionHistoryConstants::TRANSACTION_TYPE_JACKPOT) {

            if ($locked_wallet->balance < $refer_transaction->points) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_UNSETTLE,
                    $refer_transaction->points,
                    true,
                    self::STATUS_DESC_INSUFFICIENT_FUNDS,
                );

                return new ONESeamlessResponseDTO(self::inefficientFundsResponse($request_data['traceId']), 200);
            }

            $locked_wallet->debit($refer_transaction->points);

            $bet_round = $player_bet->betRound;

            $player_bet->unsettle();

            $bet_round->reopen(null, null, null, null);

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
        }

        if ($refer_transaction->transaction_type == GameTransactionHistoryConstants::TRANSACTION_TYPE_BET_AND_SETTLE) {

            if ($locked_wallet->balance < $refer_transaction->points) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                    $refer_transaction->points,
                    true,
                    self::STATUS_DESC_INSUFFICIENT_FUNDS,
                );

                return new ONESeamlessResponseDTO(self::inefficientFundsResponse($request_data['traceId']), 200);
            }

            $debit_amount = $refer_transaction->points;

            $locked_wallet->debit($debit_amount);

            $bet_transaction = $refer_transaction->referTo;

            $credit_amount = $bet_transaction->points;

            $locked_wallet->credit($credit_amount);

            $player_bet->cancel(now()->toDateTimeString());

            $total_amount = $credit_amount - $debit_amount;

            $is_withdraw = $total_amount < 0;

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                abs($total_amount),
                $is_withdraw,
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
        }

        return new ONESeamlessResponseDTO(
            self::successResponse(
                $request_data['traceId'],
                $request_data['username'],
                $request_data['currency'],
                $locked_wallet->balance
            ),
            200
        );
    }

    private static function betResult(
        $request_data,
        $player,
        $game_item,
        $locked_wallet,
        $game_transaction_history,
        $player_balance_history,
    ): ONESeamlessResponseDTO {
        $result_type = $request_data['resultType'];

        if ($result_type == self::BET_RESULT_WIN || $result_type == self::BET_RESULT_LOSE) {

            $to_settle = !(($request_data['isFreespin'] == 1 || $request_data['betAmount'] == 0) && $game_item->gamePlatform->platform_code === GamePlatformConstants::ONE_SUB_PROVIDER_PG_SOFT);

            $request_data['isFreespin'] = $to_settle ? 0 : 1;

            $player_bet = $game_item->bets()
                ->reference($request_data['betId'])
                ->statusNot(BetConstants::STATUS_CANCELED)
                ->playerId($locked_wallet->player_id)->first();

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    $request_data['winAmount'],
                    false,
                    self::STATUS_DESC_INVALID_BET,
                );

                return new ONESeamlessResponseDTO(self::invalidBetResponse($request_data['traceId']), 200);
            }

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT;

            $note = GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION;

            $credit_amount = $request_data['winAmount'];

            if ($request_data['jackpotAmount'] > 0) {

                $credit_amount += $request_data['jackpotAmount'];

                $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_JACKPOT;

                $note = GameTransactionHistoryConstants::NOTE_TYPE_JACKPOT;
            }

            $locked_wallet->credit($credit_amount);

            if ($to_settle) {

                $bet_round = $player_bet->betRound;

                $timestamp = Carbon::createFromTimestampMs($request_data['settledTime']);

                if ($player_bet->status == BetConstants::STATUS_UNSETTLED) {

                    $player_bet->settle($credit_amount, $timestamp, turnover: $request_data['effectiveTurnover'], win_loss: $request_data['winLoss']);
                } else {

                    $win_amount = $player_bet->win_amount + $credit_amount;

                    $win_loss = $player_bet->win_loss + $request_data['winLoss'];

                    $player_bet->resettle(
                        $win_amount,
                        $timestamp,
                        $player_bet->bet_on,
                        $player_bet->rebate,
                        $player_bet->comm,
                        $player_bet->valid_bet,
                        $player_bet->turnover,
                        $player_bet->odds,
                        $win_loss
                    );
                }

                if ($request_data['isEndRound'] == 1) {

                    $total_turnovers = $bet_round->settledAndResettledBets()->sum('turnover');

                    $total_valid_bets = $bet_round->settledAndResettledBets()->sum('valid_bet');

                    $total_win_amount = $bet_round->settledAndResettledBets()->sum('win_amount');

                    $total_winloss = $bet_round->settledAndResettledBets()->sum('win_loss');

                    $bet_round->close($timestamp, $total_winloss, $total_turnovers, $total_valid_bets, $total_win_amount);
                }
            }

            $game_transaction_history->gameActionSuccess(
                $type,
                $credit_amount,
                false,
                $locked_wallet->balance,
                $note,
                null,
                $player_bet->id,
                $refer_transaction->id
            );

            $player_balance_history->gameActionSuccess(
                $credit_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                $note
            );
        }

        if ($result_type == self::BET_RESULT_BET_WIN || $result_type == self::BET_RESULT_BET_LOSE) {

            $debit_amount = $request_data['betAmount'];

            if ($locked_wallet->balance < $debit_amount) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                    $debit_amount,
                    true,
                    self::STATUS_DESC_INSUFFICIENT_FUNDS,
                );

                return new ONESeamlessResponseDTO(self::inefficientFundsResponse($request_data['traceId']), 200);
            }

            $locked_wallet->debit($debit_amount);

            $bet_round = BetRound::roundReference($request_data['roundId'])->gamePlatformId($game_item->gamePlatform->id)->playerId($player->id)->first();

            if (!$bet_round) {

                $bet_round = BetRound::begin(
                    $player->id,
                    $game_item->gamePlatform->id,
                    $request_data['roundId'],
                    Carbon::createFromTimestampMs($request_data['betTime']),
                    $locked_wallet->currency,
                );
            }

            $player_bet = Bet::place(
                $debit_amount,
                null,
                $request_data['betId'],
                $bet_round->id,
                $game_item->id,
                Carbon::createFromTimestampMs($request_data['betTime']),
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

            // settle bet
            $settle_game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $request_data['transactionId'],
                $request_data['traceId'],
                $game_item->gamePlatform->id
            );

            $settle_player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_BET_AND_SETTLE;

            $note = GameTransactionHistoryConstants::NOTE_TYPE_BET_AND_SETTLE;

            $credit_amount = $request_data['winAmount'];

            if ($request_data['jackpotAmount'] > 0) {

                $credit_amount += $request_data['jackpotAmount'];

                $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_JACKPOT;

                $note = GameTransactionHistoryConstants::NOTE_TYPE_JACKPOT;
            }

            $locked_wallet->credit($credit_amount);

            $bet_round = $player_bet->betRound;

            $timestamp = Carbon::createFromTimestampMs($request_data['settledTime']);

            $player_bet->settle($credit_amount, $timestamp, turnover: $request_data['effectiveTurnover'], win_loss: $request_data['winLoss']);

            if ($request_data['isEndRound'] == 1) {

                $total_turnovers = $bet_round->settledAndResettledBets()->sum('turnover');

                $total_valid_bets = $bet_round->settledAndResettledBets()->sum('valid_bet');

                $total_win_amount = $bet_round->settledAndResettledBets()->sum('win_amount');

                $total_winloss = $bet_round->settledAndResettledBets()->sum('win_loss');

                $bet_round->close($timestamp, $total_winloss, $total_turnovers, $total_valid_bets, $total_win_amount);
            }

            $settle_game_transaction_history->gameActionSuccess(
                $type,
                $credit_amount,
                false,
                $locked_wallet->balance,
                $note,
                null,
                $player_bet->id,
                $game_transaction_history->id,
            );

            $settle_player_balance_history->gameActionSuccess(
                $credit_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                $note
            );
        }

        if ($result_type == self::BET_RESULT_END) {

            $player_bet = $game_item->bets()
                ->reference($request_data['betId'])
                ->playerId($locked_wallet->player_id)
                ->first();

            $bet_round = $player_bet?->betRound;

            $timestamp = Carbon::createFromTimestampMs($request_data['settledTime']);

            if (!$player_bet || $player_bet?->betRound->round_reference !== $request_data['roundId']) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    0,
                    false,
                    self::STATUS_DESC_INVALID_BET,
                );

                return new ONESeamlessResponseDTO(self::invalidBetResponse($request_data['traceId']), 200);
            }

            $player_bet->settle($request_data['winAmount'], $timestamp, turnover: $request_data['effectiveTurnover'], win_loss: $request_data['winLoss']);

            $total_turnovers = $bet_round->settledAndResettledBets()->sum('turnover');

            $total_valid_bets = $bet_round->settledAndResettledBets()->sum('valid_bet');

            $total_win_amount = $bet_round->settledAndResettledBets()->sum('win_amount');

            $total_winloss = $bet_round->settledAndResettledBets()->sum('win_loss');

            $bet_round->close($timestamp, $total_winloss, $total_turnovers, $total_valid_bets, $total_win_amount);
        }

        return new ONESeamlessResponseDTO(
            self::successResponse(
                $request_data['traceId'],
                $request_data['username'],
                $request_data['currency'],
                $locked_wallet->balance
            ),
            200
        );
    }

    private static function placeBet(
        $request_data,
        $player,
        $game_item,
        $locked_wallet,
        $game_transaction_history,
        $player_balance_history,
    ): ONESeamlessResponseDTO {
        $debit_amount = abs($request_data['amount']);

        if ($locked_wallet->balance < $debit_amount) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $debit_amount,
                true,
                self::STATUS_DESC_INSUFFICIENT_FUNDS,
            );

            return new ONESeamlessResponseDTO(self::inefficientFundsResponse($request_data['traceId']), 200);
        }

        $locked_wallet->debit($debit_amount);

        $bet_round = BetRound::roundReference($request_data['roundId'])->gamePlatformId($game_item->gamePlatform->id)->playerId($player->id)->first();

        if (!$bet_round) {

            $bet_round = BetRound::begin(
                $player->id,
                $game_item->gamePlatform->id,
                $request_data['roundId'],
                Carbon::createFromTimestampMs($request_data['timestamp']),
                $locked_wallet->currency,
            );
        }

        $player_bet = Bet::place(
            $debit_amount,
            null,
            $request_data['betId'],
            $bet_round->id,
            $game_item->id,
            Carbon::createFromTimestampMs($request_data['timestamp']),
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

        return new ONESeamlessResponseDTO(
            self::successResponse(
                $request_data['traceId'],
                $request_data['username'],
                $request_data['currency'],
                $locked_wallet->balance
            ),
            200
        );
    }

    private static function successResponse($trace, $username, $currency, $balance)
    {
        return [
            'traceId' => $trace,
            'status' => self::STATUS_CODE_SUCCESS,
            'data' => [
                'username' => $username,
                'currency' => $currency,
                'balance' => self::roundBalance($balance)
            ],
        ];
    }

    private static function invalidBetResponse($trace_id)
    {
        return [
            'traceId' => $trace_id,
            'status' => self::STATUS_CODE_INVALID_BET,
            'desc' => self::STATUS_DESC_INVALID_BET,
        ];
    }

    private static function gameItemNotFoundResponse($trace_id)
    {
        return [
            'traceId' => $trace_id,
            'status' => self::STATUS_CODE_GAME_NOT_FOUND,
            'desc' => self::STATUS_DESC_GAME_NOT_FOUND,
        ];
    }

    private static function inefficientFundsResponse($trace_id)
    {
        return [
            'traceId' => $trace_id,
            'status' => self::STATUS_CODE_INSUFFICIENT_FUNDS,
            'desc' => self::STATUS_DESC_INSUFFICIENT_FUNDS,
        ];
    }

    private static function userNotFoundResponse($trace_id)
    {
        return [
            'traceId' => $trace_id,
            'status' => self::STATUS_CODE_USER_NOT_FOUND,
            'desc' => self::STATUS_DESC_USER_NOT_FOUND,
        ];
    }

    private static function duplicatedRequestResponse($trace_id)
    {
        return [
            'traceId' => $trace_id,
            'status' => self::STATUS_CODE_DUPLICATED_REQUEST,
            'desc' => self::STATUS_DESC_DUPLICATED_REQUEST,
        ];
    }

    private static function currencyMismatchResponse($trace_id)
    {
        return [
            'traceId' => $trace_id,
            'status' => self::STATUS_CODE_CURRENCY_MISMATCH,
            'desc' => self::STATUS_DESC_CURRENCY_MISMATCH,
        ];
    }

    private static function unknownErrorResponse($trace_id)
    {
        return [
            'traceId' => $trace_id,
            'status' => self::STATUS_CODE_UNKNOWN_ERROR,
            'desc' => self::STATUS_DESC_UNKNOWN_ERROR,
        ];
    }

    private static function authFailedResponse($trace_id)
    {
        return [
            'traceId' => $trace_id,
            'status' => self::STATUS_CODE_AUTH_FAILED,
            'desc' => self::STATUS_DESC_AUTH_FAILED,
        ];
    }

    private static function validationErrorResponse($error, $trace_id)
    {
        return [
            'traceId' => $trace_id,
            'status' => self::STATUS_CODE_VALIDATION_FAILED,
            'desc' => self::STATUS_DESC_VALIDATION_FAILED,
            'error' => $error,
        ];
    }

    private static function ipNotAllowedResponse($ip, $trace_id)
    {
        return [
            'traceId' => $trace_id,
            'status' => self::STATUS_CODE_IP_WHITELIST,
            'desc' => self::STATUS_DESC_IP_WHITELIST,
            'ip' => $ip,
        ];
    }
}
