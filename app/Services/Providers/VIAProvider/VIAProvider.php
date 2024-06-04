<?php

namespace App\Services\Providers\VIAProvider;

use Exception;
use Carbon\Carbon;
use App\Models\Bet;
use App\Models\User;
use App\Models\Player;
use GuzzleHttp\Client;
use App\Models\BetRound;
use App\Models\GameItem;
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
use App\Constants\GameTransactionHistoryConstants;
use App\Services\Providers\VIAProvider\Enums\VIACurrencyEnums;
use App\Services\Providers\VIAProvider\DTOs\VIASeamlessResponseDTO;
use App\Services\Providers\VIAProvider\Enums\VIATransactionBehaviorEnums;

class VIAProvider implements ProviderInterface
{
    //codes Descriptions
    const CODE_DESC_SUCCESS = 'Success';
    const CODE_DESC_AUTH_FAILED = 'authorization failed.';
    const CODE_DESC_IP_NOT_ALLOWED = 'ip Not allowed';
    const CODE_DESC_BEHAVIOR_NOT_SUPPORTED_ERROR = 'Behavior Not Supported';
    const CODE_DESC_REFERENCE_BEHAVIOR_NOT_SUPPORTED_ERROR = 'Reference Behavior Not Supported';
    const CODE_DESC_NO_USER = 'The player does not exist';
    const CODE_DESC_INSUFFICIENT_BALANCE = 'The balance is insufficient';
    const CODE_DESC_NO_REFER_TRANSACTION = 'Bet Record not found';
    const CODE_DESC_REFERENCE_FORMAT_INCORRECT = 'reference format incorrect';
    const CODE_DESC_UNKNOWN_ERROR = 'Unknown error';
    const CODE_DESC_DUPLICATED_TRANSACTION_ERROR = 'Repeat balance changes.';
    const CODE_DESC_CURRENCY_MISMATCH = 'Currency Mismatch';
    const CODE_DESC_GAME_NOT_FOUND = 'The game is not found';

    //codes
    const CODE_SUCCESS = 0;
    const CODE_NO_USER = 70000;
    const CODE_INSUFFICIENT_BALANCE = 70001;
    const CODE_UNKNOWN_ERROR = 9999;
    const CODE_DUPLICATED_TRANSACTION_ERROR = 70003;
    const CODE_UPDATE_BALANCE_FAILED = 70002;
    const CODE_GAME_NOT_FOUND = 1009;
    const CODE_WALLET_TYPE_INCORRECT = 1031;
    const CODE_ACCESS_DENIED = 1024;

    //errors
    const ERROR_CODE_CURRENCY_NOT_SUPPORTED = 1;

    //reference separator
    const REFERENCE_SEPARATOR = '~~';

    protected $game_id;
    protected $username;
    protected $password;
    protected $vendor_id;
    protected $base_url;
    protected $headers;
    protected $game_provider_id;
    protected $language;
    protected $api_key;

    function __construct(Player $player, $game_id)
    {
        $this->username = $player->user->user_name;
        $this->game_id = $game_id;
        $this->password = $this->username . GamePlatformConstants::PLATFORM_VIA;
        $via_currency = self::getGameCurrency($player->wallet->currency);
        $credentials = self::getCredentials($via_currency);
        $this->vendor_id = $credentials['vendor_id'];
        $this->base_url = $credentials['base_url'];
        $this->api_key = $credentials['api_key'];
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'authorization' => $this->api_key,
        ];
        $this->game_provider_id = $credentials['game_provider_id'];
        $this->language = 'en';
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        if (is_null($this->vendor_id)) {

            $result = [
                'error' => self::ERROR_CODE_CURRENCY_NOT_SUPPORTED,
            ];

            return json_encode($result);
        }

        $data = [
            'gameProviderId' => $this->game_provider_id,
            'langKey' => $this->language,
            'vendorId' => $this->vendor_id,
            'vendorPlayerId' => $this->username,
            // 'launchConfig' => [
            //     'gameCode' => $this->game_id,
            // ],
        ];

        try {

            $response = Http::withHeaders($this->headers)->post($this->base_url . '/core-service/api/v1/player/launch', $data);

            return $response->body();
            // return json_encode([
            //     'request' => $data,
            //     'response' => $response->json(),
            // ]);

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('VIA Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        if (is_null($this->vendor_id)) {

            $result = [
                'error' => self::ERROR_CODE_CURRENCY_NOT_SUPPORTED,
            ];

            return json_encode($result);
        }

        $data = [
            'vendorId' => $this->vendor_id,
            'vendorPlayerId' => $this->username,
            'nickname' => $this->username,
        ];

        try {

            $response = Http::withHeaders($this->headers)->post($this->base_url . '/core-service/api/v1/player/register', $data);

            return $response->body();

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('VIA Provider Call registerToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public static function authorizeProvider($md5, VIACurrencyEnums $via_currency)
    {
        $credentials = self::getCredentials($via_currency);

        return $md5 === md5($credentials['vendor_id'] . $credentials['api_key']);
    }

    public static function getCredentials(?VIACurrencyEnums $via_currency)
    {
        switch ($via_currency) {
            case VIACurrencyEnums::VNDK:
                $vendor_id = Config::get('app.via_vendor_id.vndk');
                break;
            case VIACurrencyEnums::PHP:
                $vendor_id = Config::get('app.via_vendor_id.php');
                break;
            case VIACurrencyEnums::INR:
                $vendor_id = Config::get('app.via_vendor_id.inr');
                break;
            default:
                $vendor_id = null;
        }

        $base_url = Config::get('app.via_base_url');
        $api_key = Config::get('app.via_access_key');
        $game_provider_id = Config::get('app.via_game_provider_id');

        return [
            'vendor_id' => $vendor_id,
            'base_url' => $base_url,
            'api_key' => $api_key,
            'game_provider_id' => $game_provider_id,
        ];
    }

    public static function roundBalance($balance)
    {
        return round($balance, 4);
    }

    public static function getGameCurrency($currency): ?VIACurrencyEnums
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => VIACurrencyEnums::VNDK,
            GlobalConstants::CURRENCY_PHP => VIACurrencyEnums::PHP,
            GlobalConstants::CURRENCY_INR => VIACurrencyEnums::INR,
            default => null
        };
    }

    public static function unknownError(): VIASeamlessResponseDTO
    {
        return new VIASeamlessResponseDTO(self::unknownErrorResponse(), 400);
    }

    public static function ipNotAllowed($ip): VIASeamlessResponseDTO
    {
        return new VIASeamlessResponseDTO(self::ipNotAllowedResponse($ip), 400);
    }

    public static function authFailed(): VIASeamlessResponseDTO
    {
        return new VIASeamlessResponseDTO(self::authFailedResponse(), 400);
    }

    public static function validationError($error): VIASeamlessResponseDTO
    {
        return new VIASeamlessResponseDTO(self::validationErrorResponse($error), 400);
    }

    public static function generateOrderId($order_id, $vendor_id)
    {
        $separator = self::REFERENCE_SEPARATOR;
        return $order_id . $separator . $vendor_id;
    }

    public static function parseOrderId($unique_id)
    {
        $separator = self::REFERENCE_SEPARATOR;
        $parts = explode($separator, $unique_id);

        if (count($parts) === 2) {
            return [
                'order_id' => $parts[0],
                'vendor_id' => $parts[1],
            ];
        }

        return [
            'order_id' => null,
            'vendor_id' => null,
        ];

        // return null;
    }

    public static function generateOrderStatusId($order_id, $vendor_id, $behavior)
    {
        $separator = self::REFERENCE_SEPARATOR;
        return $order_id . $separator . $vendor_id . $separator . $behavior;
    }

    public static function parseOrderStatusId($unique_id)
    {
        $separator = self::REFERENCE_SEPARATOR;
        $parts = explode($separator, $unique_id);

        if (count($parts) === 3) {
            return [
                'order_id' => $parts[0],
                'vendor_id' => $parts[1],
                'behavior' => $parts[2],
            ];
        }

        // Handle error or invalid format
        return null;
    }


    public static function walletGetBalance($vendor_player_id, $requested_currency): VIASeamlessResponseDTO
    {
        $user = User::where('user_name', $vendor_player_id)->first();

        if (!$user) {
            return new VIASeamlessResponseDTO(self::userNotFoundResponse(), 400);
        }

        $player = $user->player;
        $locked_wallet = $player->wallet()->lockForUpdate()->first();
        $balance = $locked_wallet->balance;

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if ($player_game_currency != $requested_currency) {
            return new VIASeamlessResponseDTO(self::currencyMismatchResponse(), 400);
        }

        return new VIASeamlessResponseDTO(self::successResponse($vendor_player_id, $balance), 200);
    }

    public static function walletChangeBalance(array $validated_request, VIACurrencyEnums $requested_currency): VIASeamlessResponseDTO
    {
        $user = User::where('user_name', $validated_request['vendorPlayerId'])->first();

        if (!$user) {
            return new VIASeamlessResponseDTO(self::userNotFoundResponse(), 400);
        }

        $player = $user->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $amount = abs($validated_request['transactionAmount']);

        $behavior = VIATransactionBehaviorEnums::tryFrom($validated_request['transactionBehavior']);

        if ($behavior === null) {
            return new VIASeamlessResponseDTO(self::behaviorNotSupportedResponse(), 400);
        }

        $game_item = GameItem::where('game_id', GamePlatformConstants::VIA_GAME_CODE_LOBBY)->first();

        if (!$game_item) {
            return new VIASeamlessResponseDTO(self::gameItemNotFoundResponse(), 400);
        }

        $reference_no = self::generateOrderStatusId($validated_request['orderId'], $validated_request['vendorId'], $behavior->value);

        $dup_transaction = $game_item->gameTransactionHistories()->referenceNo($reference_no)->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

        if ($dup_transaction) {

            return new VIASeamlessResponseDTO(self::duplicatedTransactionResponse(), 400);
        }

        $is_withdraw = $validated_request['transactionAmount'] < 0;

        $transaction_type = $validated_request['transactionAmount'] < 0 ? GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT : GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT;

        $game_transaction_history = GameTransactionHistory::gameAction(
            $locked_wallet->balance,
            $player->id,
            $locked_wallet->currency,
            $locked_wallet->id,
            $game_item->id,
            $reference_no,
            null,
            $game_item->gamePlatform->id
        );

        $player_balance_history = PlayerBalanceHistory::gameAction(
            $player->id,
            $locked_wallet->balance,
            $locked_wallet->currency,
        );

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if ($player_game_currency != $requested_currency) {

            $game_transaction_history->gameActionFailed(
                $transaction_type,
                $amount,
                $is_withdraw,
                self::CODE_DESC_CURRENCY_MISMATCH,
                $validated_request['transactionId']
            );

            return new VIASeamlessResponseDTO(self::currencyMismatchResponse(), 400);
        }

        switch ($behavior) {
            case VIATransactionBehaviorEnums::BET:
                return self::processBetBehavior(
                    $game_transaction_history,
                    $player_balance_history,
                    $player,
                    $locked_wallet,
                    $amount,
                    $validated_request['transactionId'],
                    $validated_request['vendorPlayerId'],
                    $game_item,
                    $validated_request['orderId'],
                    $validated_request['vendorId'],
                    $validated_request['betOrder'],
                    $validated_request['betOrder']['gameCode']
                );
            case VIATransactionBehaviorEnums::TIP:
                return self::processTipBehavior(
                    $game_transaction_history,
                    $player_balance_history,
                    $locked_wallet,
                    $amount,
                    $validated_request['transactionId'],
                    $validated_request['vendorPlayerId']
                );
            case VIATransactionBehaviorEnums::SETTLE:
                return self::processSettleBehavior(
                    $game_transaction_history,
                    $player_balance_history,
                    $locked_wallet,
                    $amount,
                    $validated_request['transactionId'],
                    $validated_request['vendorPlayerId'],
                    $game_item,
                    $validated_request['orderId'],
                    $validated_request['vendorId'],
                    $validated_request['betOrder']
                );
            case VIATransactionBehaviorEnums::RESETTLE:
                return self::processResettleBehavior(
                    $game_transaction_history,
                    $player_balance_history,
                    $locked_wallet,
                    $amount,
                    $is_withdraw,
                    $game_item,
                    $validated_request['transactionId'],
                    $validated_request['vendorId'],
                    $validated_request['vendorPlayerId'],
                    $validated_request['orderId'],
                    $validated_request['betOrder']
                );
            case VIATransactionBehaviorEnums::VOID_CANCEL:
                return self::processVoidCancelBehavior(
                    $game_transaction_history,
                    $player_balance_history,
                    $locked_wallet,
                    $amount,
                    $is_withdraw,
                    $game_item,
                    $validated_request['transactionId'],
                    $validated_request['vendorId'],
                    $validated_request['vendorPlayerId'],
                    $validated_request['orderId'],
                    $validated_request['betOrder']
                );
            case VIATransactionBehaviorEnums::CANCELLED:
                return self::processCanceledBehavior(
                    $game_transaction_history,
                    $player_balance_history,
                    $locked_wallet,
                    $amount,
                    $game_item,
                    $validated_request['transactionId'],
                    $validated_request['vendorId'],
                    $validated_request['vendorPlayerId'],
                    $validated_request['orderId'],
                    $validated_request['betOrder'] ?? null,
                    $validated_request['tipOrder'] ?? null
                );
            default:
                return new VIASeamlessResponseDTO(self::behaviorNotSupportedResponse(), 400);
        }

    }

    private static function processCanceledBehavior(
        $game_transaction_history,
        $player_balance_history,
        $locked_wallet,
        $amount,
        $game_item,
        $transfer_no,
        $vendor_id,
        $vendor_player_id,
        $order_id,
        $bet_order,
        $tip_order
    ): VIASeamlessResponseDTO {

        if ($bet_order) {

            $reference = self::generateOrderId($order_id, $vendor_id);

            $bet = $game_item->bets()->status(BetConstants::STATUS_UNSETTLED)->reference($reference)->playerId($locked_wallet->player_id)->first();

            if (!$bet) {

                return new VIASeamlessResponseDTO(self::successResponse($vendor_player_id, $locked_wallet->balance), 200);
            }

            $refer_transaction = $bet->latestSuccessfulGameTransactionHistory;

        } elseif ($tip_order) {

            $reference = self::generateOrderStatusId($order_id, $vendor_id, VIATransactionBehaviorEnums::TIP->value);

            $refer_transaction = $game_item->gameTransactionHistories()->referenceNo($reference)->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

            if (!$refer_transaction) {

                return new VIASeamlessResponseDTO(self::successResponse($vendor_player_id, $locked_wallet->balance), 200);
            }
        }

        $locked_wallet->credit($refer_transaction->points);

        if (isset($bet)) {

            $bet->cancel(Carbon::createFromTimestampMs($bet_order['updateTime']));

            $bet->betRound->close(Carbon::createFromTimestampMs($bet_order['updateTime']), null);
        }

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
            $refer_transaction->points,
            false,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION,
            $transfer_no,
            isset($bet) ? $bet->id : null,
            $refer_transaction->id,
        );

        $player_balance_history->gameActionSuccess(
            $refer_transaction->points,
            false,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION
        );

        return new VIASeamlessResponseDTO(self::successResponse($vendor_player_id, $locked_wallet->balance), 200);

    }

    private static function processVoidCancelBehavior(
        $game_transaction_history,
        $player_balance_history,
        $locked_wallet,
        $amount,
        $is_withdraw,
        $game_item,
        $transfer_no,
        $vendor_id,
        $vendor_player_id,
        $order_id,
        $bet_order
    ): VIASeamlessResponseDTO {

        $reference = self::generateOrderId($order_id, $vendor_id);

        $bet = $game_item->bets()->statusIn([
            BetConstants::STATUS_UNSETTLED,
            BetConstants::STATUS_SETTLED,
            BetConstants::STATUS_RESETTLED
        ])->reference($reference)->playerId($locked_wallet->player_id)->first();

        if (!$bet) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $amount,
                $is_withdraw,
                self::CODE_DESC_NO_REFER_TRANSACTION,
                $transfer_no
            );

            return new VIASeamlessResponseDTO(self::noReferTransactionResponse(), 400);
        }

        $refer_transaction = $bet->latestSuccessfulGameTransactionHistory;

        $is_withdraw ? $locked_wallet->debit($amount) : $locked_wallet->credit($amount);

        $bet->cancel(Carbon::createFromTimestampMs($bet_order['updateTime']));

        $bet->betRound->close(Carbon::createFromTimestampMs($bet_order['updateTime']), null);

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
            $amount,
            $is_withdraw,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION,
            $transfer_no,
            $bet->id,
            $refer_transaction->id,
        );

        $player_balance_history->gameActionSuccess(
            $amount,
            $is_withdraw,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION
        );

        return new VIASeamlessResponseDTO(self::successResponse($vendor_player_id, $locked_wallet->balance), 200);
    }

    private static function processResettleBehavior(
        $game_transaction_history,
        $player_balance_history,
        $locked_wallet,
        $amount,
        $is_withdraw,
        $game_item,
        $transfer_no,
        $vendor_id,
        $vendor_player_id,
        $order_id,
        $bet_order
    ): VIASeamlessResponseDTO {

        $reference = self::generateOrderId($order_id, $vendor_id);

        $bet = $game_item->bets()->status(BetConstants::STATUS_SETTLED)->reference($reference)->playerId($locked_wallet->player_id)->first();

        $type = $amount === 0
            ? GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG
            : ($is_withdraw
                ? GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT
                : GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT);

        if (!$bet) {

            $game_transaction_history->gameActionFailed(
                $type,
                $amount,
                $is_withdraw,
                self::CODE_DESC_NO_REFER_TRANSACTION,
                $transfer_no
            );

            return new VIASeamlessResponseDTO(self::noReferTransactionResponse(), 400);
        }

        $refer_transaction = $bet->latestSuccessfulGameTransactionHistory;

        $bet_round = $bet->betRound;

        if ($amount !== 0) {

            // if deduction no amount was won so equal to "0"
            $is_withdraw ? $win_amount = 0 : $win_amount = $amount;

        } else { // no change in behavior (win->win) or (loss->loss)

            $win_amount = $bet->win_amount;
        }

        $is_withdraw ? $locked_wallet->debit($amount) : $locked_wallet->credit($amount);

        $bet_round->reclose(
            Carbon::createFromTimestampMs($bet_order['settleTime']),
            $bet_order['winloss'],
            $bet_order['device'],
            null,
            null,
            null,
            $bet_order['validBet'],
            $win_amount
        );

        $bet->resettle(
            $win_amount,
            Carbon::createFromTimestampMs($bet_order['settleTime']),
            Carbon::createFromTimestampMs($bet_order['betTime']),
            $bet_order['rebate'],
            null,
            $bet_order['validBet']
        );

        $game_transaction_history->gameActionSuccess(
            $type,
            $amount,
            $is_withdraw,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_RESETTLE_BET,
            $transfer_no,
            $bet->id,
            $refer_transaction->id,
        );

        $player_balance_history->gameActionSuccess(
            $amount,
            $is_withdraw,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_RESETTLE_BET
        );

        return new VIASeamlessResponseDTO(self::successResponse($vendor_player_id, $locked_wallet->balance), 200);
    }

    private static function processSettleBehavior(
        $game_transaction_history,
        $player_balance_history,
        $locked_wallet,
        $amount,
        $transfer_no,
        $vendor_player_id,
        $game_item,
        $order_id,
        $vendor_id,
        $bet_order
    ): VIASeamlessResponseDTO {

        $reference = self::generateOrderId($order_id, $vendor_id);

        $bet = $game_item->bets()->status(BetConstants::STATUS_UNSETTLED)->reference($reference)->playerId($locked_wallet->player_id)->first();

        $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_LOSE_BET;
        $note = GameTransactionHistoryConstants::NOTE_PLAYER_LOST_BET;

        if ($amount !== 0) {
            $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_WIN_BET;
            $note = GameTransactionHistoryConstants::NOTE_PLAYER_WON_BET;
        }

        if (!$bet) {

            $game_transaction_history->gameActionFailed(
                $type,
                $amount,
                false,
                self::CODE_DESC_NO_REFER_TRANSACTION,
                $transfer_no
            );

            return new VIASeamlessResponseDTO(self::noReferTransactionResponse(), 400);
        }

        $locked_wallet->credit($amount);

        $bet_round = $bet->betRound;

        $timestamp = Carbon::createFromTimestampMs($bet_order['settleTime']);

        $bet->settle($amount, $timestamp, $bet_order['rebate'], null, $bet_order['validBet']);

        $bet_round->close($timestamp, $bet_order['winloss'], $bet_order['validBet'], $bet_order['validBet'], $amount);

        $game_transaction_history->gameActionSuccess(
            $type,
            $amount,
            false,
            $locked_wallet->balance,
            $note,
            $transfer_no,
            $bet->id,
            $bet->latestSuccessfulGameTransactionHistory->id
        );

        $player_balance_history->gameActionSuccess(
            $amount,
            false,
            $locked_wallet->balance,
            $game_transaction_history->id,
            $note
        );

        return new VIASeamlessResponseDTO(self::successResponse($vendor_player_id, $locked_wallet->balance), 200);
    }

    private static function processBetBehavior(
        $game_transaction_history,
        $player_balance_history,
        $player,
        $locked_wallet,
        $amount,
        $transfer_no,
        $vendor_player_id,
        $game_item,
        $order_id,
        $vendor_id,
        $bet_order,
        $game_code
    ): VIASeamlessResponseDTO {

        if ($locked_wallet->balance < $amount) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $amount,
                true,
                self::CODE_DESC_INSUFFICIENT_BALANCE,
                $transfer_no
            );

            return new VIASeamlessResponseDTO(self::insufficientBalanceResponse(), 400);
        }

        $locked_wallet->debit($amount);

        $reference = self::generateOrderId($order_id, $vendor_id);

        $bet_round = BetRound::begin(
            $player->id,
            $game_item->gamePlatform->id,
            $reference,
            Carbon::createFromTimestampMs($bet_order['betTime']),
            $locked_wallet->currency,
            $bet_order['device'],
        );

        $bet = Bet::place(
            $amount,
            $bet_round->round_reference,
            $reference,
            $bet_round->id,
            $game_item->id,
            Carbon::createFromTimestampMs($bet_order['betTime']),
            $locked_wallet->currency,
            null,
            $bet_order['rebate'],
            null,
            $game_code
        );

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
            $amount,
            true,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET,
            $transfer_no,
            $bet->id,
        );

        $player_balance_history->gameActionSuccess(
            $amount,
            true,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET
        );

        return new VIASeamlessResponseDTO(self::successResponse($vendor_player_id, $locked_wallet->balance), 200);
    }

    private static function processTipBehavior(
        $game_transaction_history,
        $player_balance_history,
        $locked_wallet,
        $amount,
        $transfer_no,
        $vendor_player_id
    ): VIASeamlessResponseDTO {

        if ($locked_wallet->balance < $amount) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_TIP,
                $amount,
                true,
                self::CODE_DESC_INSUFFICIENT_BALANCE,
                $transfer_no
            );

            new VIASeamlessResponseDTO(self::insufficientBalanceResponse(), 400);
        }

        $locked_wallet->debit($amount);

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_TIP,
            $amount,
            true,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_TIP,
            $transfer_no,
        );

        $player_balance_history->gameActionSuccess(
            $amount,
            true,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_TIP
        );

        return new VIASeamlessResponseDTO(self::successResponse($vendor_player_id, $locked_wallet->balance), 200);
    }

    private static function referenceIncorrectFormatResponse()
    {
        return [
            'code' => self::CODE_UNKNOWN_ERROR,
            'message' => self::CODE_DESC_REFERENCE_FORMAT_INCORRECT,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function noReferTransactionResponse()
    {
        return [
            'code' => self::CODE_UNKNOWN_ERROR,
            'message' => self::CODE_DESC_NO_REFER_TRANSACTION,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function insufficientBalanceResponse()
    {
        return [
            'code' => self::CODE_INSUFFICIENT_BALANCE,
            'message' => self::CODE_DESC_INSUFFICIENT_BALANCE,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function referenceBehaviorNotSupportedResponse()
    {
        return [
            'code' => self::CODE_UNKNOWN_ERROR,
            'message' => self::CODE_DESC_REFERENCE_BEHAVIOR_NOT_SUPPORTED_ERROR,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function behaviorNotSupportedResponse()
    {
        return [
            'code' => self::CODE_UNKNOWN_ERROR,
            'message' => self::CODE_DESC_BEHAVIOR_NOT_SUPPORTED_ERROR,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function duplicatedTransactionResponse()
    {
        return [
            'code' => self::CODE_DUPLICATED_TRANSACTION_ERROR,
            'message' => self::CODE_DESC_DUPLICATED_TRANSACTION_ERROR,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function userNotFoundResponse()
    {
        return [
            'code' => self::CODE_NO_USER,
            'message' => self::CODE_DESC_NO_USER,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function gameItemNotFoundResponse()
    {
        return [
            'code' => self::CODE_GAME_NOT_FOUND,
            'message' => self::CODE_DESC_GAME_NOT_FOUND,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function validationErrorResponse($error)
    {
        return [
            'code' => self::CODE_UNKNOWN_ERROR,
            'message' => $error,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function authFailedResponse()
    {
        return [
            'code' => self::CODE_ACCESS_DENIED,
            'message' => self::CODE_DESC_AUTH_FAILED,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function ipNotAllowedResponse($ip)
    {
        return [
            'code' => self::CODE_ACCESS_DENIED,
            'message' => self::CODE_DESC_IP_NOT_ALLOWED,
            'ip' => $ip,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function currencyMismatchResponse()
    {
        return [
            'code' => self::CODE_WALLET_TYPE_INCORRECT,
            'message' => self::CODE_DESC_CURRENCY_MISMATCH,
            'serverTime' => (int) now()->valueOf(),
        ];
    }

    private static function unknownErrorResponse()
    {
        return [
            'code' => self::CODE_UNKNOWN_ERROR,
            'message' => self::CODE_DESC_UNKNOWN_ERROR,
            'serverTime' => (int) now()->valueOf()
        ];
    }


    private static function successResponse($vendor_player_id, $balance)
    {
        return [
            'code' => self::CODE_SUCCESS,
            'data' => [
                'vendorPlayerId' => $vendor_player_id,
                'balance' => self::roundBalance($balance),
                'transactionTime' => (int) now()->valueOf(),
            ],
            'message' => self::CODE_DESC_SUCCESS,
            'serverTime' => (int) now()->valueOf(),
        ];
    }
}