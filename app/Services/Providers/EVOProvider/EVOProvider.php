<?php

namespace App\Services\Providers\EVOProvider;

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
use App\Constants\EVOProviderConfigConstants;
use App\Services\Providers\ProviderInterface;
use App\Constants\GameTransactionHistoryConstants;
use App\Services\Providers\EVOProvider\DTOs\EVOConfigDTO;
use App\Services\Providers\EVOProvider\Enums\EVOActionsEnums;
use App\Services\Providers\EVOProvider\Enums\EVOCurrencyEnums;
use App\Services\Providers\EVOProvider\DTOs\EVOSeamlessResponseDTO;

class EVOProvider implements ProviderInterface
{
    //supported statuses
    const STATUS_DESC_SUCCESS_RESPONSE = 'OK';
    const STATUS_DESC_FAILED = 'UNKNOWN_ERROR';
    const STATUS_DESC_AUTH_FAILED = 'INVALID_TOKEN_ID';
    const STATUS_DESC_VALIDATION_ERROR = 'INVALID_PARAMETER';
    const STATUS_DESC_DUPLICATED_PLACE_BET_REQUEST = 'BET_ALREADY_EXIST';
    const STATUS_DESC_DUPLICATED_SETTLE_BET_REQUEST = 'BET_ALREADY_EXIST';
    const STATUS_DESC_TEMPORARY_ERROR = 'TEMPORARY_ERROR';
    const STATUS_DESC_INSUFFICIENT_FUNDS = 'INSUFFICIENT_FUNDS';
    const STATUS_DESC_BET_DOES_NOT_EXIST = 'BET_DOES_NOT_EXIST';

    // messages
    const MESSAGE_USER_NOT_FOUND = 'USER_NOT_FOUND';
    const MESSAGE_IP_NOT_ALLOWED = 'IP_NOT_ALLOWED';
    const MESSAGE_CURRENCY_MISMATCH = 'CURRENCY_MISMATCH';
    const MESSAGE_ACTION_NOT_SUPPORTED = 'ACTION_NOT_SUPPORTED';
    const MESSAGE_GAME_ITEM_NOT_FOUND = 'GAME_ITEM_NOT_FOUND';

    // reference  
    const REFERENCE_SEPARATOR = '~~';

    // game view 
    const GAME_VIEW_VIEW1 = 'view1'; // launch game in 3D view
    const GAME_VIEW_VIEW2 = 'view2'; // launch game in classic view
    const GAME_VIEW_MLR = 'MLR'; // launch game in mini live roulette view
    const GAME_VIEW_SLINGSHOT = 'Slingshot'; // launch game in slingshot view (for auto-roulette only)
    const GAME_VIEW_HD1 = 'hd1'; // roulette immersive view and used in CSP.

    // languages
    const LANG_EN = 'en';
    const LANG_VN = 'vi';
    const LANG_HI = 'hi';

    // countries
    const COUNTRY_PH = 'PH';
    const COUNTRY_VN = 'VN';
    const COUNTRY_IN = 'IN';

    protected $user_id;
    protected $first_name;
    protected $last_name;
    protected $nickname;
    protected $country;
    protected $language;
    protected $currency;
    protected $session;
    protected $group_id;
    protected $group_action;
    protected $game_category;
    protected $table_id;
    protected $channel_wrapped;
    protected $channel_mobile;
    protected $casino_key;
    protected $api_token;
    protected $base_url;
    protected $headers;

    function __construct(Player $player, $game_code)
    {
        $this->currency = self::getGameCurrency($player->wallet->currency);
        $credentials = self::getCredential($this->currency);
        $this->casino_key = $credentials['casino_key'];
        $this->api_token = $credentials['api_token'];
        $this->base_url = $credentials['base_url'];
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $this->language = self::getGameLanguage($player->language);
        $this->user_id = $player->user->user_name;
        $this->group_id = null;
        $this->first_name = $player->user->user_name;
        $this->last_name = $player->user->user_name;
        $this->nickname = $player->user->user_name;
        $this->country = self::getGameCountry($player->wallet->currency);
        $this->session = Uuid::uuid4()->toString();
        $this->group_action = "assign"; //"clear"
        $this->table_id = $game_code;
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        try {

            $wrapped = false;

            $mobile = false;

            if ($deviceType == 'Mobile') {

                $wrapped = true;

                $mobile = true;
            }

            $data = [
                'uuid' => Uuid::uuid4()->toString(),
                'player' => [
                    'id' => $this->user_id,
                    'update' => true,
                    'firstName' => $this->first_name,
                    'lastName' => $this->last_name,
                    'country' => $this->country,
                    'nickname' => $this->nickname,
                    'language' => $this->language,
                    'currency' => $this->currency,
                    'session' => [
                        'id' => $this->session,
                        'ip' => $loginIp
                    ],
                    // 'group' => [
                    //     'id' => $this->group_id,
                    //     'action' => $this->group_action
                    // ],
                ],
                'config' => [
                    // 'game' => [
                    //     'interface' => self::GAME_VIEW_VIEW2,
                    //     'table' => [
                    //         'id' => $this->table_id,
                    //     ],
                    // ],
                    'channel' => [
                        'wrapped' => $wrapped,
                        'mobile' => $mobile,
                    ],
                ],
            ];

            $response = Http::withHeaders($this->headers)->post($this->base_url . '/ua/v1/' . $this->casino_key . '/' . $this->api_token, $data);

            return $response->body();
            // return json_encode([
            //     'api_url' => $this->base_url . '/ua/v1/' . $this->casino_key . '/' . $this->api_token,
            //     'request_headers' => $this->headers,
            //     'request_data' => $data,
            //     'response' => $response->body()
            // ]);

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('EVO Provider Call loginToGame API Exception');
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
        switch ($language) {
            case GlobalConstants::LANG_EN:
                $language = self::LANG_EN;
                break;
            case GlobalConstants::LANG_VN:
                $language = self::LANG_VN;
                break;
            case GlobalConstants::LANG_HI:
                $language = self::LANG_HI;
                break;
            default:
                $language = self::LANG_EN;
        }

        return $language;
    }

    public static function getCredential(EVOCurrencyEnums $evo_currency)
    {
        $casino_key = null;

        $api_token = null;

        switch ($evo_currency) {
            case EVOCurrencyEnums::VNDK:
                $casino_key = Config::get('app.evo_casino_key.vndk');
                $api_token = Config::get('app.evo_api_token.vndk');
                break;
            case EVOCurrencyEnums::PHP:
                $casino_key = Config::get('app.evo_casino_key.php');
                $api_token = Config::get('app.evo_api_token.php');
                break;
            case EVOCurrencyEnums::INR:
                $casino_key = Config::get('app.evo_casino_key.inr');
                $api_token = Config::get('app.evo_api_token.inr');
                break;
        }

        return [
            'casino_key' => $casino_key,
            'api_token' => $api_token,
            'base_url' => Config::get('app.evo_base_url'),
        ];
    }

    public static function getGameCountry($currency)
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => self::COUNTRY_VN,
            GlobalConstants::CURRENCY_PHP => self::COUNTRY_PH,
            GlobalConstants::CURRENCY_INR => self::COUNTRY_IN,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getSystemCurrency(EVOCurrencyEnums $currency): int
    {
        return match ($currency) {
            EVOCurrencyEnums::VNDK => GlobalConstants::CURRENCY_VNDK,
            EVOCurrencyEnums::PHP => GlobalConstants::CURRENCY_PHP,
            EVOCurrencyEnums::INR => GlobalConstants::CURRENCY_INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getGameCurrency($currency): EVOCurrencyEnums
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => EVOCurrencyEnums::VNDK,
            GlobalConstants::CURRENCY_PHP => EVOCurrencyEnums::PHP,
            GlobalConstants::CURRENCY_INR => EVOCurrencyEnums::INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function roundBalance($balance)
    {
        return round($balance, 2);
    }

    public static function authorizeProvider($auth_token, EVOCurrencyEnums $evo_currency)
    {
        $credentials = self::getCredential($evo_currency);

        return ($auth_token === $credentials['api_token']);
    }

    public static function unknownError(): EVOSeamlessResponseDTO
    {
        return new EVOSeamlessResponseDTO(self::unknownErrorResponse(), 500);
    }

    public static function authFailed(): EVOSeamlessResponseDTO
    {
        return new EVOSeamlessResponseDTO(self::authFailedResponse(), 401);
    }

    public static function validationError($error): EVOSeamlessResponseDTO
    {
        return new EVOSeamlessResponseDTO(self::validationErrorResponse($error), 422);
    }

    public static function ipNotAllowed($ip): EVOSeamlessResponseDTO
    {
        return new EVOSeamlessResponseDTO(self::ipNotAllowedResponse($ip), 401);
    }

    public static function walletAccess($data, EVOCurrencyEnums $currency, EVOActionsEnums $wallet_action): EVOSeamlessResponseDTO
    {
        $user = User::where('user_name', $data['userId'])->first();

        if (!$user) {
            return new EVOSeamlessResponseDTO(self::userNotFoundResponse(), 400);
        }

        $game_item = GameItem::where('game_id', GamePlatformConstants::EVO_GAME_CODE_LOBBY)->first();

        if (!$game_item) {

            return new EVOSeamlessResponseDTO(self::gameItemNotFoundResponse(), 400);
        }

        $is_dup_transaction = GameTransactionHistory::transactionRequestNo($data['uuid'])->gamePlatformId($game_item->gamePlatform->id)->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

        $player = $user->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        return match ($wallet_action) {
            EVOActionsEnums::CHECK => new EVOSeamlessResponseDTO(self::successResponse(null), 200),
            EVOActionsEnums::BALANCE => self::getBalance($player, $locked_wallet, $currency, $data),
            EVOActionsEnums::DEBIT => self::placeBet($player, $locked_wallet, $currency, $data, $game_item, $is_dup_transaction),
            EVOActionsEnums::CREDIT => self::settleBet($player, $locked_wallet, $currency, $data, $game_item, $is_dup_transaction),
            EVOActionsEnums::CANCEL => self::cancelBet($player, $locked_wallet, $currency, $data, $game_item, $is_dup_transaction),
            default => new EVOSeamlessResponseDTO(self::actionNotSupportedResponse(), 500),
        };
    }

    private static function cancelBet($player, $locked_wallet, EVOCurrencyEnums $requested_currency, $data, $game_item, $is_dup_transaction): EVOSeamlessResponseDTO
    {
        if ($is_dup_transaction) {

            return new EVOSeamlessResponseDTO(self::duplicatedSettleBetRequest(), 400);
        }

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        $game_currency = EVOCurrencyEnums::tryFrom($data['currency']);

        $game_transaction_history = GameTransactionHistory::gameAction(
            $locked_wallet->balance,
            $player->id,
            $locked_wallet->currency,
            $locked_wallet->id,
            $game_item->id,
            $data['transaction']['id'],
            $data['uuid'],
            $game_item->gamePlatform->id
        );

        $player_balance_history = PlayerBalanceHistory::gameAction(
            $player->id,
            $locked_wallet->balance,
            $locked_wallet->currency,
        );

        if ($player_game_currency !== $requested_currency || $player_game_currency !== $game_currency) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $data['transaction']['amount'],
                false,
                self::MESSAGE_CURRENCY_MISMATCH,
            );

            return new EVOSeamlessResponseDTO(self::currencyMismatchResponse(), 400);
        }

        $refer_transaction = $game_item->gameTransactionHistories()->gameTransactionNo($data['transaction']['refId'])->playerId($locked_wallet->player_id)->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

        $player_bet = null;

        if ($refer_transaction) {

            $player_bet = $refer_transaction->bet()->status(BetConstants::STATUS_UNSETTLED)->playerId($locked_wallet->player_id)->first();
        }

        if (!$player_bet) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $data['transaction']['amount'],
                false,
                self::STATUS_DESC_BET_DOES_NOT_EXIST,
            );

            return new EVOSeamlessResponseDTO(self::invalidBetResponse(), 400);
        }

        $bet_round = $player_bet->betRound;

        if ($bet_round->round_reference !== $data['game']['id']) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $data['transaction']['amount'],
                false,
                self::STATUS_DESC_BET_DOES_NOT_EXIST,
            );

            return new EVOSeamlessResponseDTO(self::invalidBetResponse(), 400);
        }

        $locked_wallet->credit($data['transaction']['amount']);

        $timestamp = now()->toDateTimeString();

        $player_bet->cancel($timestamp);

        $bet_round = $player_bet->betRound;

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
            $data['transaction']['amount'],
            false,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION,
            null,
            $player_bet->id,
            $refer_transaction->id,
            $bet_round->id
        );

        $player_balance_history->gameActionSuccess(
            $data['transaction']['amount'],
            false,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION
        );

        return new EVOSeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function settleBet($player, $locked_wallet, EVOCurrencyEnums $requested_currency, $data, $game_item, $is_dup_transaction): EVOSeamlessResponseDTO
    {
        if ($is_dup_transaction) {

            return new EVOSeamlessResponseDTO(self::duplicatedSettleBetRequest(), 400);
        }

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        $game_currency = EVOCurrencyEnums::tryFrom($data['currency']);

        // init game transaction history and save the uuid as a requestNo and transaction.id as reference 
        $game_transaction_history = GameTransactionHistory::gameAction(
            $locked_wallet->balance,
            $player->id,
            $locked_wallet->currency,
            $locked_wallet->id,
            $game_item->id,
            $data['transaction']['id'],
            $data['uuid'],
            $game_item->gamePlatform->id
        );

        // init balance history
        $player_balance_history = PlayerBalanceHistory::gameAction(
            $player->id,
            $locked_wallet->balance,
            $locked_wallet->currency,
        );

        // check currency
        if ($player_game_currency !== $requested_currency || $player_game_currency !== $game_currency) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                $data['transaction']['amount'],
                false,
                self::MESSAGE_CURRENCY_MISMATCH,
            );

            return new EVOSeamlessResponseDTO(self::currencyMismatchResponse(), 400);
        }

        // get the transaction using the transaction.refId then get the bet from it
        $refer_transaction = $game_item->gameTransactionHistories()
            ->gameTransactionNo($data['transaction']['refId'])
            ->playerId($locked_wallet->player_id)
            ->status(GameTransactionHistoryConstants::STATUS_SUCCESS)
            ->first();

        $player_bet = null;

        if ($refer_transaction) {

            $player_bet = $refer_transaction->bet()->status(BetConstants::STATUS_UNSETTLED)->playerId($locked_wallet->player_id)->first();
        }

        // check if the bet/transaction exists , check if the bet status is Unsettled then proceed 

        if (!$player_bet) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                $data['transaction']['amount'],
                false,
                self::STATUS_DESC_BET_DOES_NOT_EXIST,
            );

            return new EVOSeamlessResponseDTO(self::invalidBetResponse(), 400);
        }

        // get the bet round from the bet , check if the bet round reference is the same as game.id
        $bet_round = $player_bet->betRound;

        if ($bet_round->round_reference !== $data['game']['id']) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                $data['transaction']['amount'],
                false,
                self::STATUS_DESC_BET_DOES_NOT_EXIST,
            );

            return new EVOSeamlessResponseDTO(self::invalidBetResponse(), 400);
        }

        $player_bets = $bet_round->bets;

        $locked_wallet->credit($data['transaction']['amount']);

        $total_bet_amount = $player_bets->sum('bet_amount');

        $round_win_loss = $data['transaction']['amount'] - $total_bet_amount;

        $timestamp = now()->toDateTimeString();

        $bet_round->close($timestamp, $round_win_loss, $data['transaction']['amount'], $data['transaction']['amount'], $data['transaction']['amount']);

        foreach ($player_bets as $player_bet) {

            $player_bet->settle(null, $timestamp);
        }

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
            $data['transaction']['amount'],
            false,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION,
            null,
            null,
            $refer_transaction->id,
            $bet_round->id
        );

        $player_balance_history->gameActionSuccess(
            $data['transaction']['amount'],
            false,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION
        );

        return new EVOSeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function placeBet($player, $locked_wallet, EVOCurrencyEnums $requested_currency, $data, $game_item, $is_dup_transaction): EVOSeamlessResponseDTO
    {
        if ($is_dup_transaction) {

            return new EVOSeamlessResponseDTO(self::duplicatedPlaceBetRequest(), 400);
        }

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        $game_currency = EVOCurrencyEnums::tryFrom($data['currency']);

        // init game transaction history and save the uuid as a requestNo and transaction.id as reference 
        $game_transaction_history = GameTransactionHistory::gameAction(
            $locked_wallet->balance,
            $player->id,
            $locked_wallet->currency,
            $locked_wallet->id,
            $game_item->id,
            $data['transaction']['id'],
            $data['uuid'],
            $game_item->gamePlatform->id
        );

        // init balance history
        $player_balance_history = PlayerBalanceHistory::gameAction(
            $player->id,
            $locked_wallet->balance,
            $locked_wallet->currency,
        );

        // check currency 
        if ($player_game_currency !== $requested_currency || $player_game_currency !== $game_currency) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $data['transaction']['amount'],
                true,
                self::MESSAGE_CURRENCY_MISMATCH,
            );

            return new EVOSeamlessResponseDTO(self::currencyMismatchResponse(), 400);
        }

        // check if user has balance
        if ($locked_wallet->balance < $data['transaction']['amount']) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $data['transaction']['amount'],
                true,
                self::STATUS_DESC_INSUFFICIENT_FUNDS,
            );

            return new EVOSeamlessResponseDTO(self::insufficientFundsResponse($locked_wallet->balance), 400);
        }

        // debit the player
        $locked_wallet->debit($data['transaction']['amount']);

        // get the round ,check if round exists using the game.id(round.id) else create new round using the round id
        $bet_round = BetRound::roundReference($data['game']['id'])->gamePlatformId($game_item->gamePlatform->id)->playerId($player->id)->first();

        if (!$bet_round) {

            $bet_round = BetRound::begin(
                $player->id,
                $game_item->gamePlatform->id,
                $data['game']['id'],
                now()->toDateTimeString(),
                $locked_wallet->currency,
            );
        }

        // create a bet with transaction.id as reference and game.type as game name
        $player_bet = Bet::place(
            $data['transaction']['amount'],
            null,
            $data['transaction']['refId'],
            $bet_round->id,
            $game_item->id,
            now()->toDateTimeString(),
            $locked_wallet->currency,
            null,
            null,
            null,
            $data['game']['type']
        );

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
            $data['transaction']['amount'],
            true,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET,
            $data['transaction']['refId'],
            $player_bet->id,
        );

        $player_balance_history->gameActionSuccess(
            $data['transaction']['amount'],
            true,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET
        );

        return new EVOSeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function getBalance($player, $locked_wallet, EVOCurrencyEnums $requested_currency, $data): EVOSeamlessResponseDTO
    {
        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        $game_currency = EVOCurrencyEnums::tryFrom($data['currency']);

        if ($player_game_currency !== $requested_currency || $player_game_currency !== $game_currency) {
            return new EVOSeamlessResponseDTO(self::currencyMismatchResponse(), 400);
        }

        return new EVOSeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function invalidBetResponse()
    {
        return [
            'status' => self::STATUS_DESC_BET_DOES_NOT_EXIST,
        ];
    }

    private static function insufficientFundsResponse($balance)
    {
        return [
            'status' => self::STATUS_DESC_INSUFFICIENT_FUNDS,
            'balance' => self::roundBalance($balance),
        ];
    }

    private static function gameItemNotFoundResponse()
    {
        return [
            'status' => self::STATUS_DESC_FAILED,
            'message' => self::MESSAGE_GAME_ITEM_NOT_FOUND,
        ];
    }

    private static function currencyMismatchResponse()
    {
        return [
            'status' => self::STATUS_DESC_FAILED,
            'message' => self::MESSAGE_CURRENCY_MISMATCH,
        ];
    }
    private static function duplicatedSettleBetRequest()
    {
        return [
            'status' => self::STATUS_DESC_DUPLICATED_SETTLE_BET_REQUEST,
            'uuid' => Uuid::uuid4()->toString(),
        ];
    }

    private static function duplicatedPlaceBetRequest()
    {
        return [
            'status' => self::STATUS_DESC_DUPLICATED_PLACE_BET_REQUEST,
            'uuid' => Uuid::uuid4()->toString(),
        ];
    }

    private static function actionNotSupportedResponse()
    {
        return [
            'status' => self::STATUS_DESC_FAILED,
            'message' => self::MESSAGE_ACTION_NOT_SUPPORTED,
        ];
    }

    private static function authFailedResponse()
    {
        return [
            'status' => self::STATUS_DESC_AUTH_FAILED,
        ];
    }

    private static function ipNotAllowedResponse($ip)
    {
        return [
            'status' => self::STATUS_DESC_FAILED,
            'message' => self::MESSAGE_IP_NOT_ALLOWED,
            'ip' => $ip,
        ];
    }

    private static function unknownErrorResponse()
    {
        return [
            'status' => self::STATUS_DESC_FAILED,
        ];
    }

    private static function validationErrorResponse($error)
    {
        return [
            'status' => self::STATUS_DESC_VALIDATION_ERROR,
            'error' => $error
        ];
    }

    private static function userNotFoundResponse()
    {
        return [
            'status' => self::STATUS_DESC_FAILED,
            'message' => self::MESSAGE_USER_NOT_FOUND,
        ];
    }

    private static function successResponse($balance)
    {
        return [
            'status' => self::STATUS_DESC_SUCCESS_RESPONSE,
            'balance' => $balance ? self::roundBalance($balance) : null,
            'sid' => Uuid::uuid4()->toString(),
            'uuid' => Uuid::uuid4()->toString(),
        ];
    }
}
