<?php

namespace App\Services\Providers\KMProvider;

use Exception;
use Carbon\Carbon;
use App\Models\Bet;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Models\Player;
use GuzzleHttp\Client;
use App\Models\BetRound;
use App\Models\GameItem;
use Illuminate\Support\Str;
use App\Models\GamePlatform;
use App\Constants\BetConstants;
use App\Models\KMProviderConfig;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Log;
use App\Constants\BetRoundConstants;
use App\Models\PlayerBalanceHistory;
use Illuminate\Support\Facades\Http;
use App\Models\GameTransactionHistory;
use Illuminate\Support\Facades\Config;
use App\Constants\GamePlatformConstants;
use App\Constants\KMProviderConfigConstants;
use App\Services\Providers\ProviderInterface;
use App\Constants\GameTransactionHistoryConstants;
use App\Services\Providers\KMProvider\DTOs\KMConfigDTO;
use App\Services\Providers\KMProvider\Enums\KMCurrencyEnums;
use App\Services\Providers\KMProvider\DTOs\KMASeamlessResponseDTO;

class KMProvider implements ProviderInterface
{
    // error descriptions
    const ERROR_DESC_VALIDATION = 'Invalid arguments';
    const ERROR_DESC_NO_USER = 'Invalid arguments: userid not exist.';
    const ERROR_DESC_CREDIT_TXTYPE_NOT_SUPPORTED = 'Invalid arguments: txtype not Supported for Credit';
    const ERROR_DESC_DEBIT_TXTYPE_NOT_SUPPORTED = 'Invalid arguments: txtype not Supported for Debit';
    const ERROR_DESC_CURRENCY_MISMATCH = 'Currency Mismatch';
    const ERROR_DESC_TRANSACTION_DOES_NOT_EXIST = 'Transaction does not exist';
    const ERROR_DESC_INSUFFICIENT_FUNDS = 'Insufficient funds to perform the operation';
    const ERROR_DESC_INVALID_CREDENTIALS = 'Invalid Credential';
    const ERROR_DESC_OPERATION_FAILED_DETERMINISTICALLY = 'Operation Failed Deterministically';
    const ERROR_DESC_IP_NOT_ALLOWED = 'Operation Failed Deterministically: IP is not allowed';
    const ERROR_DESC_GAME_CODE_NOT_SUPPORTED = 'Operation Failed Deterministically: gamecode not supported';
    const ERROR_DESC_ROUND_NOT_FOUND = 'Operation Failed Deterministically: round not found';
    const ERROR_DESC_BET_NOT_FOUND = 'Operation Failed Deterministically: bet not found';
    const ERROR_DESC_SYSTEM_ERROR = 'System Error.';

    //error codes
    const ERROR_CODE_CURRENCY_NOT_SUPPORTED = 1;
    const ERROR_CODE_INVALID_ARGUMENTS = 300;
    const ERROR_CODE_CURRENCY_MISMATCH = 50;
    const ERROR_CODE_INSUFFICIENT_FUNDS = 100;
    const ERROR_CODE_TRANSACTION_DOES_NOT_EXIST = 600;
    const ERROR_CODE_INVALID_CREDENTIALS = 30;
    const ERROR_CODE_OPERATION_FAILED_DETERMINISTICALLY = 800;
    const ERROR_CODE_SYSTEM_ERROR = 900;

    // credit transaction types
    const CREDIT_TX_WIN_BET = 510;
    const CREDIT_TX_LOSE_BET = 520;
    const CREDIT_TX_FREE_BET = 530;
    const CREDIT_TX_FUND_IN_WALLET = 600;
    const CREDIT_TX_CANCEL_TRANSACTION = 560;
    const CREDIT_TX_CANCEL_FUND_OUT = 611;

    // debit transaction types
    const DEBIT_TX_PLACE_BET = 500;
    const DEBIT_TX_FUND_OUT_WALLET = 610;

    // wallet types
    const WALLET_TYPE_MAIN_WALLET = 'MainWallet';

    // languages 
    const LANG_EN = 'en-US';
    const LANG_VN = 'vi-VN';

    // devices
    const DEVICE_DESKTOP = 0;
    const DEVICE_MOBILE = 1;

    //reference separator
    const REFERENCE_SEPARATOR = '~~';

    //params
    protected $username;
    protected $name;
    protected $base_url;
    protected $game_base_url;
    protected $game_id;
    protected $lobby_path;
    protected $client_id;
    protected $client_secret;
    protected $headers;
    protected $language;
    protected $currency;
    protected $transferNo;
    protected $bet_limit;
    protected $is_test_player;

    function __construct(Player $player, $game_id)
    {
        $this->name = $player->user->user_name;
        $credentials = self::getCredential();
        $this->base_url = $credentials['base_url'];
        $this->game_base_url = $credentials['game_base_url'];
        $this->game_id = $game_id;
        $this->lobby_path = $credentials['lobby_path'];
        $this->client_id = $credentials['client_id'];
        $this->client_secret = $credentials['client_secret'];
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-QM-ClientId' => $this->client_id,
            'X-QM-ClientSecret' => $this->client_secret,
        ];
        $this->language = self::getGameLanguage($player->language);
        $this->currency = self::getGameCurrency($player->wallet->currency);
        $config = self::getKMconfig($player);
        $this->username = $config->user_id;
        $this->bet_limit = $config->bet_limit;
        $this->is_test_player = false;
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        switch ($deviceType) {
            case 'Mobile':
                $deviceType = 1;
                break;
            case 'Tablet':
                $deviceType = 0;
                $this->lobby_path = Config::get('app.km_lobby_pc_path');
                break;
            case 'Desktop':
                $deviceType = 0;
                $this->lobby_path = Config::get('app.km_lobby_pc_path');
                break;
            default:
                $deviceType = 1;
        }

        if (is_null($this->currency)) {

            $result = [
                'error' => self::ERROR_CODE_CURRENCY_NOT_SUPPORTED,
            ];

            return json_encode($result);
        }

        $data = [
            'ipaddress' => $loginIp,
            'username' => $this->name,
            'userid' => $this->username,
            'lang' => $this->language,
            'cur' => $this->currency,
            'betlimitid' => $this->bet_limit,
            'istestplayer' => $this->is_test_player,
            'platformtype' => $deviceType,
        ];

        try {

            $response = Http::withHeaders($this->headers)->post($this->base_url . '/api/player/authorize', $data);

            $result = json_decode($response->body());

            if (isset($result->authtoken)) {
                // $result->url = $this->game_base_url . '/' . $this->lobby_path . '?token=' . $result->authtoken;
                $result->url = $this->game_base_url . '/gamelauncher?gpcode=KMQM&gcode=' . $this->game_id . '&token=' . $result->authtoken;
            }

            return json_encode($result);

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('KM Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public static function getKMconfig(player $player): KMConfigDTO
    {
        if ($player->kmProviderConfig) {

            $KMconfig = $player->kmProviderConfig;

        } else {

            $KMconfig = $player->kmProviderConfig()->create([
                'vndk_user_id' => Uuid::uuid4()->toString(),
                'inr_user_id' => Uuid::uuid4()->toString(),
                'php_user_id' => Uuid::uuid4()->toString(),
                'bet_limit' => KMProviderConfigConstants::BET_LIMIT_BASIC,
            ]);
        }

        if ($player->wallet->currency == GlobalConstants::CURRENCY_VNDK) {
            return new KMConfigDTO($KMconfig->vndk_user_id, $KMconfig->bet_limit);
        }

        if ($player->wallet->currency == GlobalConstants::CURRENCY_INR) {
            return new KMConfigDTO($KMconfig->inr_user_id, $KMconfig->bet_limit);
        }

        if ($player->wallet->currency == GlobalConstants::CURRENCY_PHP) {
            return new KMConfigDTO($KMconfig->php_user_id, $KMconfig->bet_limit);
        }

        throw new Exception('KM Config Error');
    }

    public function registerToGame($language, $loginIp): ?string
    {
        return 'NOT_SUPPORTED';
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
            default:
                $language = self::LANG_EN;
        }

        return $language;
    }

    public static function getGameCurrency($currency): ?KMCurrencyEnums
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => KMCurrencyEnums::VNDK,
            GlobalConstants::CURRENCY_PHP => KMCurrencyEnums::PHP,
            GlobalConstants::CURRENCY_INR => KMCurrencyEnums::INR,
            default => throw new Exception('Currency Enum Incorrect')
        };
    }

    public static function getSystemCurrency(KMCurrencyEnums $currency): ?int
    {
        return match ($currency) {
            KMCurrencyEnums::VNDK => GlobalConstants::CURRENCY_VNDK,
            KMCurrencyEnums::PHP => GlobalConstants::CURRENCY_PHP,
            KMCurrencyEnums::INR => GlobalConstants::CURRENCY_INR,
            default => null
        };
    }

    public static function getDevices()
    {
        return [
            self::DEVICE_DESKTOP => 'Desktop',
            self::DEVICE_MOBILE => 'Mobile',
        ];
    }

    public static function getDevice($device)
    {
        return static::getDevices()[$device] ?? null;
    }

    public static function generateReference($reference, $brand_code)
    {
        $separator = self::REFERENCE_SEPARATOR;
        return $reference . $separator . $brand_code;
    }

    public static function getCredential()
    {
        return [
            'base_url' => Config::get('app.km_base_url'),
            'game_base_url' => Config::get('app.km_game_base_url'),
            'client_id' => Config::get('app.km_client_id'),
            'client_secret' => Config::get('app.km_client_secret'),
            'lobby_path' => Config::get('app.km_lobby_mobile_path'),
        ];
    }

    public static function authorizeProvider($client_id, $client_secret): bool
    {
        $credentials = self::getCredential();
        return ($client_id === $credentials['client_id'] && $client_secret === $credentials['client_secret']);
    }

    public static function RoundBalance($balance)
    {
        return round($balance, 6);
    }

    public static function getCreditTransactionTypes()
    {
        return [
            self::CREDIT_TX_WIN_BET,
            self::CREDIT_TX_LOSE_BET,
            self::CREDIT_TX_FREE_BET,
            self::CREDIT_TX_FUND_IN_WALLET,
            self::CREDIT_TX_CANCEL_TRANSACTION,
            self::CREDIT_TX_CANCEL_FUND_OUT,
        ];
    }

    public static function getDebitTransactionTypes()
    {
        return [
            self::DEBIT_TX_PLACE_BET,
            self::DEBIT_TX_FUND_OUT_WALLET,
        ];
    }

    public static function authFailed(): KMASeamlessResponseDTO
    {
        return new KMASeamlessResponseDTO(self::authFailedResponse(), 401);
    }

    public static function unknownError(): KMASeamlessResponseDTO
    {
        return new KMASeamlessResponseDTO(self::unknownErrorResponse(), 200);
    }

    public static function validationError($error): KMASeamlessResponseDTO
    {
        return new KMASeamlessResponseDTO(self::validationErrorResponse($error), 400);
    }

    public static function ipNotAllowed($ip): KMASeamlessResponseDTO
    {
        return new KMASeamlessResponseDTO(self::ipNotAllowedResponse($ip), 401);
    }

    public static function walletBalance($game_users): KMASeamlessResponseDTO
    {
        $result['users'] = [];

        foreach ($game_users as $game_user) {

            $game_currency = KMCurrencyEnums::tryFrom($game_user['cur']);

            $system_currency = self::getSystemCurrency($game_currency);

            $player_config = KMProviderConfig::userId($game_user['userid'], $system_currency)->first();

            if ($player_config) {

                $player = $player_config->player;

                $locked_wallet = $player->wallet()->lockForUpdate()->first();

                $balance = $locked_wallet->balance;

                $currency = self::getGameCurrency($locked_wallet->currency);

                if ($currency != $game_currency) {

                    $result['users'][] = self::currencyMismatchResponse($game_user['userid']);

                    continue;
                }

                $result['users'][] = self::walletBalanceSuccessResponse($game_user['userid'], $balance, $currency);
            }
        }

        return new KMASeamlessResponseDTO($result, 200);
    }

    public static function walletReward($transactions): KMASeamlessResponseDTO
    {
        $result['transactions'] = [];

        $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_KM)->first();

        foreach ($transactions as $transaction) {

            $game_currency = KMCurrencyEnums::tryFrom($transaction['cur']);

            $system_currency = self::getSystemCurrency($game_currency);

            $player_config = KMProviderConfig::userId($transaction['userid'], $system_currency)->first();

            $ptxid = $transaction['ptxid'];

            $transfer_no = Uuid::uuid4()->toString();

            if (!$player_config) {

                $result['transactions'][] = self::userNotFoundResponse($transfer_no, $ptxid);

                continue;
            }

            $player = $player_config->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $before_balance = $locked_wallet->balance;

            $dup_transaction = GameTransactionHistory::referenceNo($ptxid)->gamePlatformId($game_platform->id)->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

            if ($dup_transaction) {

                $result['transactions'][] = self::duplicatedTransactionResponse($dup_transaction->game_transaction_no, $ptxid, $locked_wallet->balance, $transaction['cur']);

                continue;
            }

            $amount = $transaction['amt'];

            $game_transaction_history = GameTransactionHistory::gameAction(
                $before_balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                null,
                $ptxid,
                null,
                $game_platform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $before_balance,
                $locked_wallet->currency,
            );

            if ($system_currency != $locked_wallet->currency) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_REWARD,
                    $amount,
                    false,
                    self::ERROR_DESC_CURRENCY_MISMATCH,
                    $transfer_no
                );

                $result['transactions'][] = self::currencyMismatchResponse($transaction['userid']);

                continue;
            }

            $locked_wallet->credit($amount);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_REWARD,
                $amount,
                false,
                $locked_wallet->balance,
                $transaction['desc'] ?? GameTransactionHistoryConstants::NOTE_TYPE_REWARD,
                $transfer_no,
            );

            $player_balance_history->gameActionSuccess(
                $amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_TYPE_REWARD
            );
        }

        return new KMASeamlessResponseDTO($result, 200);
    }

    public static function walletChangeBalance($transactions, $is_withdraw): KMASeamlessResponseDTO
    {
        $result['transactions'] = [];

        foreach ($transactions as $transaction) {

            $game_currency = KMCurrencyEnums::tryFrom($transaction['cur']);

            $system_currency = self::getSystemCurrency($game_currency);

            $player_config = KMProviderConfig::userId($transaction['userid'], $system_currency)->first();

            $txtype = $transaction['txtype'];

            $ptxid = $transaction['ptxid'];

            $refptxid = $transaction['refptxid'] ?? null;

            $transfer_no = Uuid::uuid4()->toString();

            $game_item = GameItem::where('game_id', $transaction['gamecode'])->first();

            if (!$game_item) {

                $result['transactions'][] = self::gameCodeNotSupportedResponse($transfer_no, $ptxid);

                continue;
            }

            if (!$player_config) {

                $result['transactions'][] = self::userNotFoundResponse($transfer_no, $ptxid);

                continue;
            }

            $player = $player_config->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $before_balance = $locked_wallet->balance;

            $dup_transaction = $game_item->gameTransactionHistories()->referenceNo($ptxid)->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

            $ref_transaction = $game_item->gameTransactionHistories()->referenceNo($refptxid)->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

            $round_reference = self::generateReference($transaction['roundid'], $transaction['brandcode']);

            $bet_round = BetRound::roundReference($round_reference)->gamePlatformId($game_item->gamePlatform->id)->playerId($player->id)->first();

            if ($dup_transaction) {

                $result['transactions'][] = self::duplicatedTransactionResponse($dup_transaction->game_transaction_no, $ptxid, $locked_wallet->balance, $transaction['cur']);

                continue;
            }

            $isbuyingame = $transaction['isbuyingame'];

            $amount = $transaction['amt'];

            $game_transaction_history = GameTransactionHistory::gameAction(
                $before_balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $ptxid,
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $before_balance,
                $locked_wallet->currency,
            );

            if ($is_withdraw && !in_array($txtype, self::getDebitTransactionTypes())) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT,
                    $amount,
                    true,
                    self::ERROR_DESC_DEBIT_TXTYPE_NOT_SUPPORTED,
                    $transfer_no
                );

                $result['transactions'][] = self::txtypeNotSupportedResponse($transfer_no, $ptxid);

                continue;
            }

            if (!$is_withdraw && !in_array($txtype, self::getCreditTransactionTypes())) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    $amount,
                    false,
                    self::ERROR_DESC_CREDIT_TXTYPE_NOT_SUPPORTED,
                    $transfer_no
                );

                $result['transactions'][] = self::txtypeNotSupportedResponse($transfer_no, $ptxid);

                continue;
            }

            if ($system_currency != $locked_wallet->currency) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    $amount,
                    $is_withdraw,
                    self::ERROR_DESC_CURRENCY_MISMATCH,
                    $transfer_no
                );

                $result['transactions'][] = self::currencyMismatchResponse($transaction['userid']);

                continue;
            }

            if ($txtype == self::DEBIT_TX_PLACE_BET && $isbuyingame) {

                $result['transactions'][] = self::processBetWithBuyIn(
                    $transaction,
                    $bet_round,
                    $player,
                    $game_item,
                    $locked_wallet,
                    $amount,
                    $game_transaction_history,
                    $player_balance_history,
                    $transfer_no,
                    $ptxid,
                    $game_currency
                );

                continue;
            }

            if ($is_withdraw && $locked_wallet->balance < $amount) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT,
                    $amount,
                    true,
                    self::ERROR_DESC_INSUFFICIENT_FUNDS,
                    $transfer_no
                );

                $result['transactions'][] = self::insufficientFundsResponse($transfer_no, $ptxid);

                continue;
            }

            if ($txtype == self::DEBIT_TX_PLACE_BET && !$isbuyingame) {

                $result['transactions'][] = self::processBetWithoutBuyIn(
                    $transaction,
                    $bet_round,
                    $player,
                    $game_item,
                    $locked_wallet,
                    $amount,
                    $game_transaction_history,
                    $player_balance_history,
                    $transfer_no,
                    $ptxid,
                    $game_currency
                );

                continue;
            }

            if ($txtype == self::DEBIT_TX_FUND_OUT_WALLET) {

                $result['transactions'][] = self::processFundOut(
                    $locked_wallet,
                    $amount,
                    $game_transaction_history,
                    $player_balance_history,
                    $transfer_no,
                    $ptxid,
                    $game_currency
                );

                continue;
            }

            if ($txtype == self::CREDIT_TX_WIN_BET && $isbuyingame) {

                $result['transactions'][] = self::processWinWithByIn(
                    $transaction,
                    $bet_round,
                    $locked_wallet,
                    $amount,
                    $game_transaction_history,
                    $player_balance_history,
                    $transfer_no,
                    $ptxid,
                    $game_currency,
                    $ref_transaction
                );

                continue;
            }

            if ($txtype == self::CREDIT_TX_WIN_BET && !$isbuyingame) {

                $result['transactions'][] = self::processWinWithoutByIn(
                    $transaction,
                    $bet_round,
                    $locked_wallet,
                    $amount,
                    $game_transaction_history,
                    $player_balance_history,
                    $transfer_no,
                    $ptxid,
                    $game_currency,
                    $ref_transaction
                );

                continue;
            }

            if ($txtype == self::CREDIT_TX_LOSE_BET && !$isbuyingame) {

                $result['transactions'][] = self::processLossWithoutByIn(
                    $transaction,
                    $bet_round,
                    $locked_wallet,
                    $amount,
                    $game_transaction_history,
                    $player_balance_history,
                    $transfer_no,
                    $ptxid,
                    $game_currency,
                    $ref_transaction
                );

                continue;
            }

            if ($txtype == self::CREDIT_TX_LOSE_BET && $isbuyingame) {

                $result['transactions'][] = self::processLossWithByIn(
                    $transaction,
                    $bet_round,
                    $locked_wallet,
                    $amount,
                    $game_transaction_history,
                    $player_balance_history,
                    $transfer_no,
                    $ptxid,
                    $game_currency,
                    $ref_transaction
                );

                continue;
            }

            if ($txtype == self::CREDIT_TX_FUND_IN_WALLET) {

                $result['transactions'][] = self::processFundIn(
                    $locked_wallet,
                    $amount,
                    $game_transaction_history,
                    $player_balance_history,
                    $transfer_no,
                    $ptxid,
                    $game_currency
                );

                continue;
            }

            if ($txtype == self::CREDIT_TX_FREE_BET) {

                $result['transactions'][] = self::processFreeBet(
                    $transaction,
                    $bet_round,
                    $locked_wallet,
                    $amount,
                    $game_transaction_history,
                    $player_balance_history,
                    $transfer_no,
                    $ptxid,
                    $game_currency,
                    $ref_transaction
                );

                continue;
            }

            if ($txtype == self::CREDIT_TX_CANCEL_TRANSACTION) {

                $result['transactions'][] = self::processCancelTransaction(
                    $transaction,
                    $bet_round,
                    $locked_wallet,
                    $amount,
                    $game_transaction_history,
                    $player_balance_history,
                    $transfer_no,
                    $ptxid,
                    $game_currency,
                    $ref_transaction
                );

                continue;
            }

            if ($txtype == self::CREDIT_TX_CANCEL_FUND_OUT) {

                $result['transactions'][] = self::processCancelFundOut(
                    $locked_wallet,
                    $amount,
                    $game_transaction_history,
                    $player_balance_history,
                    $transfer_no,
                    $ptxid,
                    $game_currency,
                    $ref_transaction
                );

                continue;
            }
        }

        return new KMASeamlessResponseDTO($result, 200);
    }

    private static function processCancelFundOut(
        $locked_wallet,
        $credit_amount,
        $game_transaction_history,
        $player_balance_history,
        $transfer_no,
        $ptxid,
        $game_currency,
        $ref_transaction
    ): array {

        if ($ref_transaction) {

            $locked_wallet->credit($credit_amount);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $credit_amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION,
                $transfer_no,
                null,
                $ref_transaction->id
            );

            $player_balance_history->gameActionSuccess(
                $credit_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION
            );

            return self::successResponse($transfer_no, $ptxid, $locked_wallet->balance, $game_currency->value);
        }

        return self::referTransactionResponse($transfer_no, $ptxid);
    }

    private static function processCancelTransaction(
        $transaction,
        $bet_round,
        $locked_wallet,
        $credit_amount,
        $game_transaction_history,
        $player_balance_history,
        $transfer_no,
        $ptxid,
        $game_currency,
        $ref_transaction
    ): array {

        if ($ref_transaction) {

            if (!$bet_round) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_ROUND_NOT_FOUND,
                    $transfer_no
                );

                return self::betRoundNotFoundResponse($transfer_no, $ptxid);
            }

            $bet = $ref_transaction->bet;

            $timestamp = Carbon::parse($transaction['timestamp'])->setTimezone('UTC')->toDateTimeString();

            if (!$bet || $bet?->bet_round_id != $bet_round->id) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_BET_NOT_FOUND,
                    $transfer_no
                );

                return self::betNotFoundResponse($transfer_no, $ptxid);
            }

            $bet->cancel($timestamp);

            $locked_wallet->credit($credit_amount);

            if ($transaction['isclosinground']) {

                $round_win_loss = -1 * $transaction['ggr'];

                $total_turnovers = $bet_round->settledAndResettledBets()->sum('turnover');

                $total_valid_bets = $bet_round->settledAndResettledBets()->sum('valid_bet');

                $total_win_amount = $bet_round->settledAndResettledBets()->sum('win_amount');

                $bet_round->close($timestamp, $round_win_loss, $total_turnovers, $total_valid_bets, $total_win_amount);
            }

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $credit_amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION,
                $transfer_no,
                $bet->id,
                $ref_transaction->id
            );

            $player_balance_history->gameActionSuccess(
                $credit_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION
            );

            return self::successResponse($transfer_no, $ptxid, $locked_wallet->balance, $game_currency->value);
        }

        return self::referTransactionResponse($transfer_no, $ptxid);
    }

    private static function processFreeBet(
        $transaction,
        $bet_round,
        $locked_wallet,
        $credit_amount,
        $game_transaction_history,
        $player_balance_history,
        $transfer_no,
        $ptxid,
        $game_currency,
        $ref_transaction
    ): array {

        if ($ref_transaction) {

            if (!$bet_round) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_FREE_BET,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_ROUND_NOT_FOUND,
                    $transfer_no
                );

                return self::betRoundNotFoundResponse($transfer_no, $ptxid);
            }

            $bet = $ref_transaction->bet;

            if ($bet?->bet_round_id != $bet_round->id) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_FREE_BET,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_BET_NOT_FOUND,
                    $transfer_no
                );

                return self::betNotFoundResponse($transfer_no, $ptxid);
            }

            $locked_wallet->credit($credit_amount);

            $timestamp = Carbon::parse($transaction['timestamp'])->setTimezone('UTC')->toDateTimeString();

            if ($bet->status == BetConstants::STATUS_UNSETTLED) {

                $bet->settle($credit_amount, $timestamp);

            } else {
                $win_amount = $bet->win_amount + $credit_amount;

                $bet->resettle($win_amount, $timestamp, $bet->bet_on, $bet->rebate, $bet->comm, $bet->valid_bet, $transaction['turnover'], $bet->odds);
            }

            if ($transaction['isclosinground']) {

                $round_win_loss = -1 * $transaction['ggr'];

                $total_turnovers = $bet_round->settledAndResettledBets()->sum('turnover');

                $total_valid_bets = $bet_round->settledAndResettledBets()->sum('valid_bet');

                $total_win_amount = $bet_round->settledAndResettledBets()->sum('win_amount');

                $bet_round->close($timestamp, $round_win_loss, $total_turnovers, $total_valid_bets, $total_win_amount);
            }

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_FREE_BET,
                $credit_amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_FREE_BET,
                $transfer_no,
                $bet->id,
                $ref_transaction->id
            );

            $player_balance_history->gameActionSuccess(
                $credit_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_FREE_BET
            );

            return self::successResponse($transfer_no, $ptxid, $locked_wallet->balance, $game_currency->value);
        }

        return self::referTransactionResponse($transfer_no, $ptxid);
    }

    private static function processFundIn(
        $locked_wallet,
        $credit_amount,
        $game_transaction_history,
        $player_balance_history,
        $transfer_no,
        $ptxid,
        $game_currency
    ): array {

        $locked_wallet->credit($credit_amount);

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
            $credit_amount,
            false,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_FUND_IN_WALLET,
            $transfer_no,
        );

        $player_balance_history->gameActionSuccess(
            $credit_amount,
            false,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_FUND_IN_WALLET
        );

        return self::successResponse($transfer_no, $ptxid, $locked_wallet->balance, $game_currency->value);
    }

    private static function processLossWithByIn(
        $transaction,
        $bet_round,
        $locked_wallet,
        $credit_amount,
        $game_transaction_history,
        $player_balance_history,
        $transfer_no,
        $ptxid,
        $game_currency,
        $ref_transaction
    ): array {

        if ($ref_transaction) {

            if (!$bet_round) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_ROUND_NOT_FOUND,
                    $transfer_no
                );

                return self::betRoundNotFoundResponse($transfer_no, $ptxid);
            }

            $bet = $ref_transaction->bet;

            if (!$bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_BET_NOT_FOUND,
                    $transfer_no
                );

                return self::betNotFoundResponse($transfer_no, $ptxid);
            }

            $timestamp = Carbon::parse($transaction['timestamp'])->setTimezone('UTC')->toDateTimeString();

            $bet->settle($credit_amount, $timestamp, null, null, $transaction['turnover'], $transaction['turnover']);

            if ($transaction['isclosinground']) {

                $round_win_loss = -1 * $transaction['ggr'];

                $total_turnovers = $bet_round->settledAndResettledBets()->sum('turnover');

                $total_valid_bets = $bet_round->settledAndResettledBets()->sum('valid_bet');

                $total_win_amount = $bet_round->settledAndResettledBets()->sum('win_amount');

                $bet_round->close($timestamp, $round_win_loss, $total_turnovers, $total_valid_bets, $total_win_amount);
            }

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                $credit_amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_PLYER_BOUGHT_IN_AND_LOST,
                $transfer_no,
                $bet->id,
                $ref_transaction->id
            );

            $player_balance_history->gameActionSuccess(
                $credit_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_PLYER_BOUGHT_IN_AND_LOST
            );

            return self::successResponse($transfer_no, $ptxid, $locked_wallet->balance, $game_currency->value);
        }

        return self::referTransactionResponse($transfer_no, $ptxid);
    }

    private static function processLossWithoutByIn(
        $transaction,
        $bet_round,
        $locked_wallet,
        $credit_amount,
        $game_transaction_history,
        $player_balance_history,
        $transfer_no,
        $ptxid,
        $game_currency,
        $ref_transaction
    ): array {

        if ($ref_transaction) {

            if (!$bet_round) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_LOSE_BET,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_ROUND_NOT_FOUND,
                    $transfer_no
                );

                return self::betRoundNotFoundResponse($transfer_no, $ptxid);
            }

            $bet = $ref_transaction->bet;

            if (!$bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_LOSE_BET,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_BET_NOT_FOUND,
                    $transfer_no
                );

                return self::betNotFoundResponse($transfer_no, $ptxid);
            }

            $locked_wallet->credit($credit_amount);

            $timestamp = Carbon::parse($transaction['timestamp'])->setTimezone('UTC')->toDateTimeString();

            $bet->settle($credit_amount, $timestamp);

            if ($transaction['isclosinground']) {

                $round_win_loss = -1 * $transaction['ggr'];

                $total_turnovers = $bet_round->settledAndResettledBets()->sum('turnover');

                $total_valid_bets = $bet_round->settledAndResettledBets()->sum('valid_bet');

                $total_win_amount = $bet_round->settledAndResettledBets()->sum('win_amount');

                $bet_round->close($timestamp, $round_win_loss, $total_turnovers, $total_valid_bets, $total_win_amount);
            }

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_LOSE_BET,
                $credit_amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_PLAYER_LOST_BET,
                $transfer_no,
                $bet->id,
                $ref_transaction->id
            );

            $player_balance_history->gameActionSuccess(
                $credit_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_PLAYER_LOST_BET
            );

            return self::successResponse($transfer_no, $ptxid, $locked_wallet->balance, $game_currency->value);
        }

        return self::referTransactionResponse($transfer_no, $ptxid);
    }

    private static function processWinWithoutByIn(
        $transaction,
        $bet_round,
        $locked_wallet,
        $credit_amount,
        $game_transaction_history,
        $player_balance_history,
        $transfer_no,
        $ptxid,
        $game_currency,
        $ref_transaction
    ): array {

        if ($ref_transaction) {

            if (!$bet_round) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_WIN_BET,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_ROUND_NOT_FOUND,
                    $transfer_no
                );

                return self::betRoundNotFoundResponse($transfer_no, $ptxid);
            }

            $bet = $ref_transaction->bet;

            if (!$bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_WIN_BET,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_BET_NOT_FOUND,
                    $transfer_no
                );

                return self::betNotFoundResponse($transfer_no, $ptxid);
            }

            $locked_wallet->credit($credit_amount);

            $timestamp = Carbon::parse($transaction['timestamp'])->setTimezone('UTC')->toDateTimeString();

            if ($bet->status == BetConstants::STATUS_UNSETTLED) {

                $bet->settle($credit_amount, $timestamp);

            } else {
                $win_amount = $bet->win_amount + $credit_amount;

                $bet->resettle($win_amount, $timestamp, $bet->bet_on, $bet->rebate, $bet->comm, $bet->valid_bet, $transaction['turnover'], $bet->odds);
            }

            if ($transaction['isclosinground']) {

                $round_win_loss = -1 * $transaction['ggr'];

                $total_turnovers = $bet_round->settledAndResettledBets()->sum('turnover');

                $total_valid_bets = $bet_round->settledAndResettledBets()->sum('valid_bet');

                $total_win_amount = $bet_round->settledAndResettledBets()->sum('win_amount');

                $bet_round->close($timestamp, $round_win_loss, $total_turnovers, $total_valid_bets, $total_win_amount);
            }

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_WIN_BET,
                $credit_amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_PLAYER_WON_BET,
                $transfer_no,
                $bet->id,
                $ref_transaction->id
            );

            $player_balance_history->gameActionSuccess(
                $credit_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_PLAYER_WON_BET
            );

            return self::successResponse($transfer_no, $ptxid, $locked_wallet->balance, $game_currency->value);
        }

        return self::referTransactionResponse($transfer_no, $ptxid);
    }

    private static function processWinWithByIn(
        $transaction,
        $bet_round,
        $locked_wallet,
        $credit_amount,
        $game_transaction_history,
        $player_balance_history,
        $transfer_no,
        $ptxid,
        $game_currency,
        $ref_transaction
    ): array {

        if ($ref_transaction) {

            if (!$bet_round) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_ROUND_NOT_FOUND,
                    $transfer_no
                );

                return self::betRoundNotFoundResponse($transfer_no, $ptxid);
            }

            $bet = $ref_transaction->bet;

            if (!$bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    $credit_amount,
                    false,
                    self::ERROR_DESC_BET_NOT_FOUND,
                    $transfer_no
                );

                return self::betNotFoundResponse($transfer_no, $ptxid);
            }

            $timestamp = Carbon::parse($transaction['timestamp'])->setTimezone('UTC')->toDateTimeString();

            if ($bet->status == BetConstants::STATUS_UNSETTLED) {

                $bet->settle($credit_amount, $timestamp);

            } else {
                $win_amount = $bet->win_amount + $credit_amount;

                $bet->resettle($win_amount, $timestamp, $bet->bet_on, $bet->rebate, $bet->comm, $bet->valid_bet, $transaction['turnover'], $bet->odds);
            }

            if ($transaction['isclosinground']) {

                $round_win_loss = -1 * $transaction['ggr'];

                $total_turnovers = $bet_round->settledAndResettledBets()->sum('turnover');

                $total_valid_bets = $bet_round->settledAndResettledBets()->sum('valid_bet');

                $total_win_amount = $bet_round->settledAndResettledBets()->sum('win_amount');

                $bet_round->close($timestamp, $round_win_loss, $total_turnovers, $total_valid_bets, $total_win_amount);
            }

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                $credit_amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_PLYER_BOUGHT_IN_AND_WON,
                $transfer_no,
                $bet->id,
                $ref_transaction->id
            );

            $player_balance_history->gameActionSuccess(
                $credit_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_PLYER_BOUGHT_IN_AND_WON
            );

            return self::successResponse($transfer_no, $ptxid, $locked_wallet->balance, $game_currency->value);
        }

        return self::referTransactionResponse($transfer_no, $ptxid);
    }

    private static function processFundOut(
        $locked_wallet,
        $debit_amount,
        $game_transaction_history,
        $player_balance_history,
        $transfer_no,
        $ptxid,
        $game_currency
    ): array {

        $locked_wallet->debit($debit_amount);

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT,
            $debit_amount,
            true,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_FUND_OUT_WALLET,
            $transfer_no,
        );

        $player_balance_history->gameActionSuccess(
            $debit_amount,
            true,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_FUND_OUT_WALLET
        );

        return self::successResponse($transfer_no, $ptxid, $locked_wallet->balance, $game_currency->value);
    }

    private static function processBetWithoutBuyIn(
        $transaction,
        $bet_round,
        $player,
        $game_item,
        $locked_wallet,
        $debit_amount,
        $game_transaction_history,
        $player_balance_history,
        $transfer_no,
        $ptxid,
        $game_currency
    ): array {

        $locked_wallet->debit($debit_amount);

        $bet = self::placeBet(
            $transaction,
            $bet_round,
            $ptxid,
            $player,
            $game_item,
            $locked_wallet,
            $debit_amount,
        );

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
            $debit_amount,
            true,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET,
            $transfer_no,
            $bet->id,
        );

        $player_balance_history->gameActionSuccess(
            $debit_amount,
            true,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET
        );

        return self::successResponse($transfer_no, $ptxid, $locked_wallet->balance, $game_currency->value);
    }

    private static function processBetWithBuyIn(
        $transaction,
        $bet_round,
        $player,
        $game_item,
        $locked_wallet,
        $debit_amount,
        $game_transaction_history,
        $player_balance_history,
        $transfer_no,
        $ptxid,
        $game_currency
    ): array {

        $bet = self::placeBet(
            $transaction,
            $bet_round,
            $ptxid,
            $player,
            $game_item,
            $locked_wallet,
            $debit_amount,
        );

        $game_transaction_history->gameActionSuccess(
            GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
            $debit_amount,
            true,
            $locked_wallet->balance,
            GameTransactionHistoryConstants::NOTE_PLYER_BOUGHT_IN_AND_PLACED_BET,
            $transfer_no,
            $bet->id,
        );

        $player_balance_history->gameActionSuccess(
            $debit_amount,
            true,
            $locked_wallet->balance,
            $game_transaction_history->id,
            GameTransactionHistoryConstants::NOTE_PLYER_BOUGHT_IN_AND_PLACED_BET
        );

        return self::successResponse($transfer_no, $ptxid, $locked_wallet->balance, $game_currency->value);
    }

    private static function startRound(
        $transaction,
        $player,
        $game_item,
        $locked_wallet,
    ): BetRound {

        $round_reference = self::generateReference($transaction['roundid'], $transaction['brandcode']);

        $bet_round = BetRound::begin(
            $player->id,
            $game_item->gamePlatform->id,
            $round_reference,
            Carbon::parse($transaction['timestamp'])->setTimezone('UTC')->toDateTimeString(),
            $locked_wallet->currency,
            self::getDevice($transaction['platformtype']),
            $transaction['gpcode'],
            $transaction['ipaddress'],
        );

        return $bet_round;
    }

    private static function placeBet(
        $transaction,
        $bet_round,
        $ptxid,
        $player,
        $game_item,
        $locked_wallet,
        $debit_amount,
    ): Bet {

        if (!$bet_round) {

            $bet_round = self::startRound(
                $transaction,
                $player,
                $game_item,
                $locked_wallet,
            );
        }

        $bet_reference = self::generateReference($ptxid, $transaction['brandcode']);

        $bet = Bet::place(
            $debit_amount,
            $bet_round->round_reference,
            $bet_reference,
            $bet_round->id,
            $game_item->id,
            Carbon::parse($transaction['timestamp'])->setTimezone('UTC')->toDateTimeString(),
            $locked_wallet->currency,
            null,
            null,
            $transaction['commission'] ?? null,
        );

        return $bet;
    }

    private static function referTransactionResponse($transfer_no, $ptxid)
    {
        return [
            'txid' => $transfer_no,
            'ptxid' => $ptxid,
            'err' => self::ERROR_CODE_TRANSACTION_DOES_NOT_EXIST,
            'errdesc' => self::ERROR_DESC_TRANSACTION_DOES_NOT_EXIST,
        ];
    }

    private static function betNotFoundResponse($transfer_no, $ptxid)
    {
        return [
            'txid' => $transfer_no,
            'ptxid' => $ptxid,
            'err' => self::ERROR_CODE_OPERATION_FAILED_DETERMINISTICALLY,
            'errdesc' => self::ERROR_DESC_BET_NOT_FOUND,
        ];
    }

    private static function betRoundNotFoundResponse($transfer_no, $ptxid)
    {
        return [
            'txid' => $transfer_no,
            'ptxid' => $ptxid,
            'err' => self::ERROR_CODE_OPERATION_FAILED_DETERMINISTICALLY,
            'errdesc' => self::ERROR_DESC_ROUND_NOT_FOUND,
        ];
    }

    private static function insufficientFundsResponse($transfer_no, $ptxid)
    {
        return [
            'txid' => $transfer_no,
            'ptxid' => $ptxid,
            'err' => self::ERROR_CODE_INSUFFICIENT_FUNDS,
            'errdesc' => self::ERROR_DESC_INSUFFICIENT_FUNDS,
        ];
    }


    private static function successResponse($game_transaction_no, $ptxid, $balance, $currency)
    {
        return [
            'txid' => $game_transaction_no,
            'ptxid' => $ptxid,
            'bal' => KMProvider::RoundBalance($balance),
            'cur' => $currency,
            'dup' => false,
        ];
    }

    private static function duplicatedTransactionResponse($game_transaction_no, $ptxid, $balance, $currency)
    {
        return [
            'txid' => $game_transaction_no,
            'ptxid' => $ptxid,
            'bal' => KMProvider::RoundBalance($balance),
            'cur' => $currency,
            'dup' => true,
        ];
    }

    private static function txtypeNotSupportedResponse($transfer_no, $ptxid)
    {
        return [
            'txid' => $transfer_no,
            'ptxid' => $ptxid,
            'err' => self::ERROR_CODE_INVALID_ARGUMENTS,
            'errdesc' => self::ERROR_DESC_DEBIT_TXTYPE_NOT_SUPPORTED,
        ];
    }

    private static function userNotFoundResponse($transfer_no, $ptxid)
    {
        return [
            'txid' => $transfer_no,
            'ptxid' => $ptxid,
            'err' => self::ERROR_CODE_INVALID_ARGUMENTS,
            'errdesc' => self::ERROR_DESC_NO_USER,
        ];
    }

    private static function gameCodeNotSupportedResponse($transfer_no, $ptxid)
    {
        return [
            'txid' => $transfer_no,
            'ptxid' => $ptxid,
            'err' => self::ERROR_CODE_OPERATION_FAILED_DETERMINISTICALLY,
            'errdesc' => self::ERROR_DESC_GAME_CODE_NOT_SUPPORTED
        ];
    }

    private static function currencyMismatchResponse($user_id)
    {
        return [
            'userid' => $user_id,
            'err' => self::ERROR_CODE_CURRENCY_MISMATCH,
            'errdesc' => self::ERROR_DESC_CURRENCY_MISMATCH,
        ];
    }

    private static function walletBalanceSuccessResponse($user_id, $balance, $currency)
    {
        return [
            'userid' => $user_id,
            'wallets' => [
                [
                    'code' => self::WALLET_TYPE_MAIN_WALLET,
                    'bal' => self::RoundBalance($balance),
                    'cur' => $currency,
                ]
            ],
        ];
    }

    private static function unknownErrorResponse()
    {
        return [
            'err' => self::ERROR_CODE_SYSTEM_ERROR,
            'errdesc' => self::ERROR_DESC_SYSTEM_ERROR
        ];
    }

    private static function validationErrorResponse($error)
    {
        return [
            'err' => self::ERROR_CODE_INVALID_ARGUMENTS,
            'errdesc' => self::ERROR_DESC_VALIDATION . ' ' . $error,
        ];
    }

    private static function authFailedResponse()
    {
        return [
            'err' => self::ERROR_CODE_INVALID_CREDENTIALS,
            'errdesc' => self::ERROR_DESC_INVALID_CREDENTIALS
        ];
    }

    private static function ipNotAllowedResponse($ip)
    {
        return [
            'err' => self::ERROR_CODE_OPERATION_FAILED_DETERMINISTICALLY,
            'errdesc' => self::ERROR_DESC_IP_NOT_ALLOWED,
            'ip' => $ip,
        ];
    }

}