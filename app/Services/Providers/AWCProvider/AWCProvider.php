<?php

namespace App\Services\Providers\AWCProvider;

use Exception;
use Carbon\Carbon;
use App\Models\Bet;
use App\Models\Player;
use GuzzleHttp\Client;
use App\Models\BetRound;
use App\Models\GameItem;
use App\Constants\BetConstants;
use App\Models\AWCProviderConfig;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Log;
use App\Constants\BetRoundConstants;
use App\Models\PlayerBalanceHistory;
use App\Models\GameTransactionHistory;
use Illuminate\Support\Facades\Config;
use App\Constants\GamePlatformConstants;
use App\Constants\AWCProviderConfigConstants;
use App\Services\Providers\ProviderInterface;
use App\Constants\GameTransactionHistoryConstants;
use App\Services\Providers\AWCProvider\DTOs\AWCConfigDTO;
use App\Services\Providers\AWCProvider\Enums\AWCActionEnums;
use App\Services\Providers\AWCProvider\Enums\AWCCurrencyEnums;
use App\Services\Providers\AWCProvider\DTOs\AWCSeamlessResponseDTO;

class AWCProvider implements ProviderInterface
{
    // status description
    const STATUS_DESCRIPTION_SUCCESS = 'Success';
    const STATUS_DESCRIPTION_INVALID_IP_ADDRESS = 'Invalid IP Address';
    const STATUS_DESCRIPTION_AUTH_FAILED = 'Invalid token!';
    const STATUS_DESCRIPTION_UNKNOWN_ERROR = 'Fail';
    const STATUS_DESCRIPTION_INVALID_PARAMETER = 'Invalid parameters';
    const STATUS_DESCRIPTION_INVALID_USER_ID = 'Invalid user Id';
    const STATUS_DESCRIPTION_INVALID_CURRENCY = 'Invalid Currency';
    const STATUS_DESCRIPTION_ACTION_NOT_SUPPORTED = 'Action not supported';
    const STATUS_DESCRIPTION_INSUFFICIENT_FUNDS = 'Insufficient Balance';
    const STATUS_DESCRIPTION_INVALID_BET = 'Invalid Bet';
    const STATUS_DESCRIPTION_INVALID_SETTLE_TYPE = 'Invalid Settle Type';
    const STATUS_DESCRIPTION_INVALID_GAME = 'Invalid Game';

    // status code
    const STATUS_CODE_SUCCESS = "0000";
    const STATUS_CODE_INVALID_IP_ADDRESS = "1029";
    const STATUS_CODE_AUTH_FAILED = "1008";
    const STATUS_CODE_UNKNOWN_ERROR = "9999";
    const STATUS_CODE_INVALID_PARAMETER = "1036";
    const STATUS_CODE_INVALID_USER_ID = "1000";
    const STATUS_CODE_INVALID_CURRENCY = "1004";
    const STATUS_CODE_INSUFFICIENT_FUNDS = "1018";
    const STATUS_CODE_INVALID_BET = "1044";
    const STATUS_CODE_INVALID_GAME = "1033";

    // settle types
    const SETTLE_TYPE_PLATFORM = 'platformTxId';
    const SETTLE_TYPE_REF_PLATFORM = 'refPlatformTxId';
    const SETTLE_TYPE_ROUNDID = 'roundId';

    // void type 
    const VOID_TYPE_CHEAT = 9;
    const VOID_TYPE_GAME = 2;

    //reference separator
    const REFERENCE_SEPARATOR = '~~';

    // languages
    const LANGUAGE_VIETNAMESE = 'vn';
    const LANGUAGE_ENGLISH = 'en';

    // error codes
    const ERROR_CODE_CURRENCY_NOT_SUPPORTED = 1;

    protected $user_id;
    protected $user_name;
    protected $agent_id;
    protected $security_code;
    protected $seamless_secret;
    protected $base_url;
    protected $headers;
    protected $currency;
    protected $language;
    protected $external_url;
    protected $sub_platform;
    protected $game_code;
    protected $game_type;
    protected $total_bet_limit;
    protected $aesext_bet_limit;
    protected $horsebook_bet_limit;
    protected $auto_bet_mode;


    function __construct(Player $player, $sub_platform, $game_code)
    {
        $this->user_name = $player->user->user_name;
        $awc_currency = self::getGameCurrency($player->wallet->currency);
        $credentials = self::getCredential($awc_currency);
        $this->agent_id = $credentials['agent_id'];
        $this->security_code = $credentials['security_code'];
        $this->base_url = $credentials['base_url'];
        $this->external_url = $credentials['external_url'];
        $this->seamless_secret = $credentials['seamless_secret'];
        $this->headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ];
        $config = self::getConfig($player);
        $awc_language = self::getGameLanguage($player->language);
        $this->user_id = $config->user_id;
        $this->total_bet_limit = $config->total_bet_limit;
        $this->aesext_bet_limit = $config->aesext_bet_limit;
        $this->horsebook_bet_limit = $config->horsebook_bet_limit;
        $this->auto_bet_mode = $config->auto_bet_mode;
        $this->currency = $awc_currency->value;
        $this->language = $awc_language;
        $this->sub_platform = $sub_platform;
        $this->game_code = $game_code;
        $this->game_type = GamePlatformConstants::getAWCGameType($game_code);
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        $is_mobile = $deviceType === 'Mobile' ? true : false;

        $bet_limit = null;

        $hall = null;

        if ($this->game_type === GamePlatformConstants::AWC_SUB_PROVIDER_TYPE_LIVE) {

            if ($this->sub_platform === GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $bet_limit = $this->aesext_bet_limit;

                $hall = 'SEXY';
            } elseif ($this->sub_platform === GamePlatformConstants::AWC_SUB_PROVIDER_HORSEBOOK) {

                $bet_limit = $this->horsebook_bet_limit;
            }
        }

        $data = [
            'cert' => $this->security_code,
            'agentId' => $this->agent_id,
            'userId' => $this->user_id,
            'isMobileLogin' => $is_mobile,
            'externalURL' => $this->external_url,
            'language' => $this->language,
            'platform' => $this->sub_platform,
            'gameType' => $this->game_type,
            'gameCode' => $this->game_code,
            'betLimit' => $bet_limit,
            'hall' => $hall,
            'autoBetMode' => $this->auto_bet_mode,
        ];

        try {

            $client = new Client();

            $response = $client->post($this->base_url . '/wallet/doLoginAndLaunchGame', [
                'form_params' => $data,
                'headers' => $this->headers,
            ]);

            $result = $response->getBody()->getContents();
            Log::info('AWC GAME LOGIN');
            Log::info(json_encode([
                'api_url' => $this->base_url . '/wallet/doLoginAndLaunchGame',
                'request_headers' => $this->headers,
                'request_data' => $data,
                'response' => $result
            ]));
            return $result;

            // return json_encode([
            //     'api_url' => $this->base_url . '/wallet/doLoginAndLaunchGame',
            //     'request_headers' => $this->headers,
            //     'request_data' => $data,
            //     'response' => $result
            // ]);
        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('AWC Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        $data = [
            'cert' => $this->security_code,
            'agentId' => $this->agent_id,
            'userId' => $this->user_id,
            'currency' => $this->currency,
            'betLimit' => $this->total_bet_limit,
            'language' => $this->language,
            'userName' => $this->user_name
        ];

        // return json_encode($data);

        try {

            $client = new Client();

            $response = $client->post($this->base_url . '/wallet/createMember', [
                'form_params' => $data,
                'headers' => $this->headers,
            ]);

            $result = $response->getBody()->getContents();
            Log::info('AWC GAME REGISTER');
            Log::info(json_encode([
                'api_url' => $this->base_url . '/wallet/createMember',
                'request_headers' => $this->headers,
                'request_data' => $data,
                'response' => $result
            ]));
            return $result;
        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('AWC Provider Call registerToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public static function getTableId($game_code)
    {
        return match ($game_code) {
            GamePlatformConstants::AWC_GAME_CODE_BACCARAT_CLASSIC => rand(1, 2) === 1 ? rand(1, 10) : 'C02',
            GamePlatformConstants::AWC_GAME_CODE_BACCARAT => rand(1, 2) === 1 ? rand(1, 10) : 'C02',
            GamePlatformConstants::AWC_GAME_CODE_DRAGON_TIGER => rand(31, 32),
            GamePlatformConstants::AWC_GAME_CODE_ROULETTE => rand(71, 72),
            GamePlatformConstants::AWC_GAME_CODE_RED_BLUE_DUEL => 56,
            GamePlatformConstants::AWC_GAME_CODE_TEEN_PATTI_2020 => 81,
            GamePlatformConstants::AWC_GAME_CODE_EXTRA_ANDAR_BAHAR => 101,
            GamePlatformConstants::AWC_GAME_CODE_THAI_HI_LO => 121,
            GamePlatformConstants::AWC_GAME_CODE_THAI_FISH_PRAWN_CRAB => 126,
            GamePlatformConstants::AWC_GAME_CODE_EXTRA_SICBO => 131,
            GamePlatformConstants::AWC_GAME_CODE_SEDIE => 151,
            default => null
        };
    }

    public static function RoundBalance($balance)
    {
        return round($balance, 3);
    }

    public static function getConfig(player $player): AWCConfigDTO
    {
        $is_prod = Config::get('app.env') === 'production' ? true : false;

        if ($player->awcProviderConfig) {

            $AWCconfig = $player->awcProviderConfig;
        } else {

            $AWCconfig = $player->awcProviderConfig()->create([
                'user_id' => $player->user->user_name,
                'vndk_bet_limit' => AWCProviderConfigConstants::getVNDKDefaultBetLimit(),
                'php_bet_limit' => AWCProviderConfigConstants::getPHPDefaultBetLimit($is_prod),
                'inr_bet_limit' => AWCProviderConfigConstants::getINRDefaultBetLimit(),
                'auto_bet_mode' => true,
            ]);
        }

        if ($player->wallet->currency == GlobalConstants::CURRENCY_VNDK) {
            return new AWCConfigDTO(
                $AWCconfig->user_id,
                json_encode($AWCconfig->vndk_bet_limit),
                json_encode([GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT => $AWCconfig->vndk_bet_limit[GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT]]),
                json_encode([GamePlatformConstants::AWC_SUB_PROVIDER_HORSEBOOK => $AWCconfig->vndk_bet_limit[GamePlatformConstants::AWC_SUB_PROVIDER_HORSEBOOK]]),
                $AWCconfig->auto_bet_mode
            );
        }

        if ($player->wallet->currency == GlobalConstants::CURRENCY_INR) {
            return new AWCConfigDTO(
                $AWCconfig->user_id,
                json_encode($AWCconfig->inr_bet_limit),
                json_encode([GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT => $AWCconfig->inr_bet_limit[GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT]]),
                json_encode([GamePlatformConstants::AWC_SUB_PROVIDER_HORSEBOOK => $AWCconfig->inr_bet_limit[GamePlatformConstants::AWC_SUB_PROVIDER_HORSEBOOK]]),
                $AWCconfig->auto_bet_mode
            );
        }

        if ($player->wallet->currency == GlobalConstants::CURRENCY_PHP) {
            return new AWCConfigDTO(
                $AWCconfig->user_id,
                json_encode($AWCconfig->php_bet_limit),
                json_encode([GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT => $AWCconfig->php_bet_limit[GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT]]),
                null,
                $AWCconfig->auto_bet_mode
            );
        }

        throw new Exception('Get Config Unsupported Currency');
    }

    public static function getCredential(AWCCurrencyEnums $awc_currency)
    {
        $agent_id = null;
        $security_code = null;
        $external_url = null;

        switch ($awc_currency) {
            case AWCCurrencyEnums::VNDK:
                $agent_id = Config::get('app.awc_agent_id.vndk');
                $security_code = Config::get('app.awc_security_code.vndk');
                $external_url = Config::get('app.awc_external_url.vndk');
                break;
            case AWCCurrencyEnums::PHP:
                $agent_id = Config::get('app.awc_agent_id.php');
                $security_code = Config::get('app.awc_security_code.php');
                $external_url = Config::get('app.awc_external_url.php');
                break;
            case AWCCurrencyEnums::INR:
                $agent_id = Config::get('app.awc_agent_id.inr');
                $security_code = Config::get('app.awc_security_code.inr');
                $external_url = Config::get('app.awc_external_url.inr');
                break;
            default:
                throw new Exception('Get Credentials Unsupported Currency');
        }

        return [
            'agent_id' => $agent_id,
            'security_code' => $security_code,
            'external_url' => $external_url,
            'base_url' => Config::get('app.awc_base_url'),
            'seamless_secret' => Config::get('app.awc_seamless_secret'),
        ];
    }

    public static function getGameLanguage($language)
    {
        return match ($language) {
            GlobalConstants::LANG_VN => self::LANGUAGE_VIETNAMESE,
            default => self::LANGUAGE_ENGLISH
        };
    }

    public static function getGameCurrency($currency): AWCCurrencyEnums
    {
        return match ($currency) {
            GlobalConstants::CURRENCY_VNDK => AWCCurrencyEnums::VNDK,
            GlobalConstants::CURRENCY_PHP => AWCCurrencyEnums::PHP,
            GlobalConstants::CURRENCY_INR => AWCCurrencyEnums::INR,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getSettleType()
    {
        return [
            self::SETTLE_TYPE_PLATFORM,
            self::SETTLE_TYPE_REF_PLATFORM,
            self::SETTLE_TYPE_ROUNDID,
        ];
    }


    public static function getVoidTypes()
    {
        return [
            self::VOID_TYPE_CHEAT,
            self::VOID_TYPE_GAME,
        ];
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

    public static function authorizeProvider($seamless_secret, $awc_currency)
    {
        $credentials = self::getCredential($awc_currency);

        return ($seamless_secret === $credentials['seamless_secret']);
    }

    // seamless handling
    public static function ipNotAllowed($ip): AWCSeamlessResponseDTO
    {
        return new AWCSeamlessResponseDTO(self::ipNotAllowedResponse($ip), 200);
    }

    public static function authFailed(): AWCSeamlessResponseDTO
    {
        return new AWCSeamlessResponseDTO(self::authFailedResponse(), 200);
    }

    public static function unknownError(): AWCSeamlessResponseDTO
    {
        return new AWCSeamlessResponseDTO(self::unknownErrorResponse(), 200);
    }

    public static function validationError($error): AWCSeamlessResponseDTO
    {
        return new AWCSeamlessResponseDTO(self::validationErrorResponse($error), 200);
    }

    public static function walletAccess($data, $requested_currency): AWCSeamlessResponseDTO
    {

        $action = AWCActionEnums::tryFrom($data['action']);

        if ($action === AWCActionEnums::GET_BALANCE) {
            return self::processGetBalance($data, $requested_currency);
        }

        $bets = $data['txns'];

        switch ($action) {
            case AWCActionEnums::VOID_BET:
                return self::processVoidBet($bets, $requested_currency);
            case AWCActionEnums::UNVOID_BET:
                return self::processUnvoidBet($bets, $requested_currency);
            case AWCActionEnums::SETTLE:
                return self::processSettle($bets, $requested_currency);
            case AWCActionEnums::UNSETTLE:
                return self::processUnsettle($bets, $requested_currency);
            case AWCActionEnums::VOID_SETTLE:
                return self::processVoidSettle($bets, $requested_currency);
            case AWCActionEnums::UNVOID_SETTLE:
                return self::processUnvoidSettle($bets, $requested_currency);
            case AWCActionEnums::FREE_SPIN:
                return self::processFreeSpin($bets, $requested_currency);
            case AWCActionEnums::GIVE:
                return self::processGive($bets, $requested_currency);
            case AWCActionEnums::RESETTLE:
                return self::processResettle($bets, $requested_currency);
        }

        $user_ids = array_column($bets, 'userId');

        $is_same_user_id = count(array_unique($user_ids)) === 1;

        $user_id = $is_same_user_id ? $user_ids[0] : null;

        $player_config = AWCProviderConfig::userId($user_id)->first();

        if (!$player_config) {
            return new AWCSeamlessResponseDTO(self::userNotFoundResponse(), 200);
        }

        $player = $player_config->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if (($player_game_currency != $requested_currency)) {

            return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
        }

        $platform_codes = array_column($bets, 'platform');

        $is_same_platform = count(array_unique($platform_codes)) === 1;

        $platform = $is_same_platform ? $platform_codes[0] : null;

        $game_item = null;

        if ($platform == GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

            $game_item = GameItem::where('game_id', GamePlatformConstants::AWC_GAME_CODE_AESEXY_LOBBY)->first();
        } else {

            $game_codes = array_column($bets, 'gameCode');

            $is_same_game_code = count(array_unique($game_codes)) === 1;

            $game_code = $is_same_game_code ? $game_codes[0] : null;

            $game_item = GameItem::where('game_id', $game_code)->first();
        }

        if (!$game_item) {

            return new AWCSeamlessResponseDTO(self::gameCodeNotSupportedResponse(), 200);
        }

        switch ($action) {
            case AWCActionEnums::PLACE_BET:
                return self::processPlaceBet($game_item, $bets, $player, $locked_wallet, $player_game_currency); //
            case AWCActionEnums::CANCEL_BET:
                return self::processCancelBet($game_item, $bets, $player, $locked_wallet, $player_game_currency); //
            case AWCActionEnums::ADJUST_BET:
                return self::processAdjustBet($game_item, $bets, $player, $locked_wallet, $player_game_currency); //
            case AWCActionEnums::REFUND:
                return self::processRefund($game_item, $bets, $player, $locked_wallet, $player_game_currency); //
            case AWCActionEnums::BET_N_SETTLE:
                return self::processBetNSettle($game_item, $bets, $player, $locked_wallet, $player_game_currency); //
            case AWCActionEnums::CANCEL_BET_N_SETTLE:
                return self::processCancelBetNSettle($game_item, $bets, $player, $locked_wallet, $player_game_currency); //
            default:
                return new AWCSeamlessResponseDTO(self::actionNotSupported(), 200);
        }
    }

    private static function processResettle($bets, $requested_currency): AWCSeamlessResponseDTO
    {
        $valid_reference_bets = [];

        $valid_round_bets = [];

        $user_ids = collect($bets)->pluck('userId')->unique();

        $player_configs = AWCProviderConfig::userIdIn($user_ids)->with('player')->get()->keyBy('user_id');

        $game_codes = collect($bets)->pluck('gameCode')->unique();
        $game_items = GameItem::whereIn('game_id', $game_codes)->get()->keyBy('game_id');

        $game_item = GameItem::where('game_id', GamePlatformConstants::AWC_GAME_CODE_AESEXY_LOBBY)->first();

        foreach ($bets as $bet) {

            $player = $player_configs->get($bet['userId'])?->player;

            if (!$player) {
                return new AWCSeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            if (!$game_item) {

                return new AWCSeamlessResponseDTO(self::gameCodeNotSupportedResponse(), 200);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::RESETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_bet = null;

            $bet_round = null;

            if ($bet['settleType'] === self::SETTLE_TYPE_PLATFORM) {

                $reference = $bet['platformTxId'];

                $is_duplicated = $game_item->bets()->status(BetConstants::STATUS_RESETTLED)->reference($reference)->playerId($locked_wallet->player_id)->exists();

                if ($is_duplicated) {

                    continue;
                }

                $player_bet = $game_item->bets()->status(BetConstants::STATUS_SETTLED)->reference($reference)->playerId($locked_wallet->player_id)->first();

                $bet_round = $player_bet?->betRound;
            } elseif ($bet['settleType'] === self::SETTLE_TYPE_REF_PLATFORM) {

                $reference = $bet['refPlatformTxId'];

                $is_duplicated = $game_item->bets()->status(BetConstants::STATUS_RESETTLED)->reference($reference)->playerId($locked_wallet->player_id)->exists();

                if ($is_duplicated) {

                    continue;
                }

                $player_bet = $game_item->bets()->status(BetConstants::STATUS_SETTLED)->reference($reference)->playerId($locked_wallet->player_id)->first();

                $bet_round = $player_bet?->betRound;
            } elseif ($bet['settleType'] === self::SETTLE_TYPE_ROUNDID) {

                $round_reference = self::generateReference($bet['roundId'], $bet['platform']);

                $is_duplicated = BetRound::roundReference($round_reference)
                    ->status(BetRoundConstants::STATUS_RECLOSED)
                    ->whereHas('resettledBets')
                    ->gamePlatformId($game_item->gamePlatform->id)
                    ->playerId($player->id)
                    ->exists();

                if ($is_duplicated) {

                    continue;
                }

                $bet_round = BetRound::roundReference($round_reference)
                    ->status(BetRoundConstants::STATUS_CLOSED)
                    ->whereHas('settledBets')
                    ->gamePlatformId($game_item->gamePlatform->id)
                    ->playerId($player->id)
                    ->first();
            } else {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    0,
                    false,
                    self::STATUS_DESCRIPTION_INVALID_SETTLE_TYPE,
                );

                return new AWCSeamlessResponseDTO(self::invalidSettleTypeResponse(), 200);
            }

            if (!$bet_round) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    0,
                    false,
                    self::STATUS_DESCRIPTION_INVALID_BET,
                );

                return new AWCSeamlessResponseDTO(self::invalidBetResponse(), 200);
            }

            if ($bet['settleType'] === self::SETTLE_TYPE_ROUNDID) {

                $valid_round_bets[] = [
                    'bet' => $bet,
                    'bet_round' => $bet_round,
                ];
            } else {

                $valid_reference_bets[] = [
                    'bet' => $bet,
                    'player_bet' => $player_bet,
                    'bet_round' => $bet_round,
                ];
            }
        }

        foreach ($valid_round_bets as $valid_bet) {

            $bet = $valid_bet['bet'];

            $bet->refresh();

            $bet_round = $valid_bet['bet_round'];

            $bet_round->refresh();

            $player = $player_configs->get($bet['userId'])->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            $player_bets = $bet_round->settledBets;

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($valid_bet['platformTxId'], AWCActionEnums::RESETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $amount = $bet['winAmount'] - $bet_round->total_win_amount;

            $is_withdraw = false;
            $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT;

            if ($amount < 0) {
                $is_withdraw = true;
                $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT;
            }

            $amount = abs($amount);

            $is_withdraw ? $locked_wallet->debit($amount) : $locked_wallet->credit($amount);

            $round_win_loss = $bet['winAmount'] - $bet['betAmount'];

            $timestamp = Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString();

            $bet_round->reclose(
                $timestamp,
                $round_win_loss,
                null,
                null,
                null,
                $bet['turnover'],
                $bet['turnover'],
                $bet['winAmount']
            );

            foreach ($player_bets as $player_bet) {

                $player_bet->settle(null, $timestamp);
            }

            $refer_transaction = $bet_round->latestSuccessfulGameTransactionHistory;

            $game_transaction_history->gameActionSuccess(
                $type,
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_RESETTLE_BET,
                null,
                null,
                $refer_transaction?->id,
                $bet_round?->id
            );

            $player_balance_history->gameActionSuccess(
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_RESETTLE_BET
            );
        }

        foreach ($valid_reference_bets as $valid_bet) {

            $bet = $valid_bet['bet'];

            $player_bet = $valid_bet['player_bet'];

            $player_bet->refresh();

            $bet_round = $valid_bet['bet_round'];

            $bet_round->refresh();

            $player = $player_configs->get($bet['userId'])->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::RESETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $amount = $bet['winAmount'] - $player_bet->win_amount;

            $is_withdraw = false;
            $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT;

            if ($amount < 0) {
                $is_withdraw = true;
                $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT;
            }

            $amount = abs($amount);

            $is_withdraw ? $locked_wallet->debit($amount) : $locked_wallet->credit($amount);

            $odds = $bet['gameInfo']['odds'] ?? null;

            $win_loss = $bet['winAmount'] - $player_bet->bet_amount;

            $round_win_loss = $bet_round->win_loss - $player_bet->win_loss;

            $total_turnovers = $bet_round->total_turnovers - $player_bet->turnover;

            $total_valid_bets = $bet_round->total_valid_bets - $player_bet->valid_bet;

            $total_win_amount = $bet_round->total_win_amount - $player_bet->win_amount;

            $timestamp = Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString();

            $player_bet->resettle($bet['winAmount'], $timestamp, $player_bet->bet_on, $player_bet->rebate, $player_bet->comm, $bet['turnover'], $bet['turnover'], $odds, $win_loss);

            $round_win_loss = $round_win_loss + $win_loss;

            $total_turnovers = $total_turnovers + $bet['turnover'];

            $total_valid_bets = $total_valid_bets + $bet['turnover'];

            $total_win_amount = $total_win_amount + $bet['winAmount'];

            $player_bet->betRound->reclose(
                $timestamp,
                $round_win_loss,
                null,
                null,
                null,
                $total_turnovers,
                $total_valid_bets,
                $total_win_amount
            );

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
        }

        return new AWCSeamlessResponseDTO(self::successResponse(null), 200);
    }

    private static function processSettle($bets, $requested_currency): AWCSeamlessResponseDTO
    {
        $valid_reference_bets = [];

        $valid_round_bets = [];

        $user_ids = collect($bets)->pluck('userId')->unique();

        $player_configs = AWCProviderConfig::userIdIn($user_ids)->with('player')->get()->keyBy('user_id');

        $game_codes = collect($bets)->pluck('gameCode')->unique();
        $game_items = GameItem::whereIn('game_id', $game_codes)->get()->keyBy('game_id');

        $game_item = GameItem::where('game_id', GamePlatformConstants::AWC_GAME_CODE_AESEXY_LOBBY)->first();

        foreach ($bets as $bet) {

            $player = $player_configs->get($bet['userId'])?->player;

            if (!$player) {
                return new AWCSeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            if (!$game_item) {

                return new AWCSeamlessResponseDTO(self::gameCodeNotSupportedResponse(), 200);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::SETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_bet = null;

            $bet_round = null;

            if ($bet['settleType'] === self::SETTLE_TYPE_PLATFORM) {

                $reference = $bet['platformTxId'];

                $is_duplicated = $game_item->bets()->status(BetConstants::STATUS_SETTLED)->reference($reference)->playerId($locked_wallet->player_id)->exists();

                if ($is_duplicated) {

                    continue;
                }

                $player_bet = $game_item->bets()->status(BetConstants::STATUS_UNSETTLED)->reference($reference)->playerId($locked_wallet->player_id)->first();

                $bet_round = $player_bet?->betRound;
            } elseif ($bet['settleType'] === self::SETTLE_TYPE_REF_PLATFORM) {

                $reference = $bet['refPlatformTxId'];

                $is_duplicated = $game_item->bets()->status(BetConstants::STATUS_SETTLED)->reference($reference)->playerId($locked_wallet->player_id)->exists();

                if ($is_duplicated) {

                    continue;
                }

                $player_bet = $game_item->bets()->status(BetConstants::STATUS_UNSETTLED)->reference($reference)->playerId($locked_wallet->player_id)->first();

                $bet_round = $player_bet?->betRound;
            } elseif ($bet['settleType'] === self::SETTLE_TYPE_ROUNDID) {

                $round_reference = self::generateReference($bet['roundId'], $bet['platform']);

                $is_duplicated = BetRound::roundReference($round_reference)
                    ->status(BetRoundConstants::STATUS_CLOSED)
                    ->whereHas('settledBets')
                    ->gamePlatformId($game_item->gamePlatform->id)
                    ->playerId($player->id)
                    ->exists();

                if ($is_duplicated) {

                    continue;
                }

                $bet_round = BetRound::roundReference($round_reference)
                    ->statusIn([BetRoundConstants::STATUS_OPEN, BetRoundConstants::STATUS_REOPEN])
                    ->whereHas('unsettledBets')
                    ->gamePlatformId($game_item->gamePlatform->id)
                    ->playerId($player->id)
                    ->first();
            } else {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    $bet['winAmount'],
                    false,
                    self::STATUS_DESCRIPTION_INVALID_SETTLE_TYPE,
                );

                return new AWCSeamlessResponseDTO(self::invalidSettleTypeResponse(), 200);
            }

            if (!$bet_round) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    $bet['winAmount'],
                    false,
                    self::STATUS_DESCRIPTION_INVALID_BET,
                );

                return new AWCSeamlessResponseDTO(self::invalidBetResponse(), 200);
            }

            if ($bet['settleType'] === self::SETTLE_TYPE_ROUNDID) {

                $valid_round_bets[] = [
                    'bet' => $bet,
                    'bet_round' => $bet_round,
                ];
            } else {

                $valid_reference_bets[] = [
                    'bet' => $bet,
                    'player_bet' => $player_bet,
                    'bet_round' => $bet_round,
                ];
            }
        }

        // Log::info('settled Round Type Bets');
        // Log::info(json_encode($valid_round_bets));
        // Log::info('settled Platform Type Bets');
        // Log::info(json_encode($valid_reference_bets));

        foreach ($valid_round_bets as $valid_bet) {

            $bet = $valid_bet['bet'];

            $bet_round = $valid_bet['bet_round'];

            $bet_round->refresh();

            $player = $player_configs->get($bet['userId'])->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            $player_bets = $bet_round->unsettledBets;

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::SETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $locked_wallet->credit($bet['winAmount']);

            $round_win_loss = $bet['winAmount'] - $bet['betAmount'];

            $timestamp = Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString();

            $bet_round->close($timestamp, $round_win_loss, $bet['turnover'], $bet['turnover'], $bet['winAmount']);

            foreach ($player_bets as $player_bet) {

                $player_bet->settle(null, $timestamp);
            }

            $refer_transaction = $bet_round->latestSuccessfulGameTransactionHistory;

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                $bet['winAmount'],
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION,
                null,
                null,
                $refer_transaction?->id,
                $bet_round->id
            );

            $player_balance_history->gameActionSuccess(
                $bet['winAmount'],
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION
            );
        }

        foreach ($valid_reference_bets as $valid_bet) {

            $bet = $valid_bet['bet'];

            $player_bet = $valid_bet['player_bet'];

            $player_bet->refresh();

            $bet_round = $valid_bet['bet_round'];

            $bet_round->refresh();

            $player = $player_configs->get($bet['userId'])->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::SETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $locked_wallet->credit($bet['winAmount']);

            $odds = $bet['gameInfo']['odds'] ?? null;

            $win_loss = $bet['winAmount'] - $player_bet->bet_amount;

            $player_bet->settle($bet['winAmount'], Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString(), null, null, $bet['turnover'], $bet['turnover'], $odds, $win_loss);

            $round_win_loss = $bet_round->win_loss ? $bet_round->win_loss + $win_loss : $win_loss;

            $total_turnovers = $bet_round->total_turnovers ? $bet_round->total_turnovers + $bet['turnover'] : $bet['turnover'];

            $total_valid_bets = $bet_round->total_valid_bets ? $bet_round->total_valid_bets + $bet['turnover'] : $bet['turnover'];

            $total_win_amount = $bet_round->total_win_amount ? $bet_round->total_win_amount + $bet['winAmount'] : $bet['winAmount'];

            $bet_round->close(Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString(), $round_win_loss, $total_turnovers, $total_valid_bets, $total_win_amount);

            // Log::info('Round After Update');
            // Log::info($bet_round);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                $bet['winAmount'],
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

        return new AWCSeamlessResponseDTO(self::successResponse(null), 200);
    }

    private static function processFreeSpin($bets, $requested_currency): AWCSeamlessResponseDTO
    {
        $user_ids = collect($bets)->pluck('userId')->unique();

        $player_configs = AWCProviderConfig::userIdIn($user_ids)->with('player')->get()->keyBy('user_id');

        $game_codes = collect($bets)->pluck('gameCode')->unique();
        $game_items = GameItem::whereIn('game_id', $game_codes)->get()->keyBy('game_id');

        $game_item = GameItem::where('game_id', GamePlatformConstants::AWC_GAME_CODE_AESEXY_LOBBY)->first();

        $valid_bets = [];

        foreach ($bets as $bet) {

            $player = $player_configs->get($bet['userId'])?->player;

            if (!$player) {
                return new AWCSeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            if (!$game_item) {

                return new AWCSeamlessResponseDTO(self::gameCodeNotSupportedResponse(), 200);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::FREE_SPIN->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_bet = $game_item->bets()->reference($bet['refPlatformTxId'])->playerId($locked_wallet->player_id)->first();

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    $bet['winAmount'],
                    false,
                    self::STATUS_DESCRIPTION_INVALID_BET,
                );

                return new AWCSeamlessResponseDTO(self::invalidBetResponse(), 200);
            }

            $valid_bets[$bet['refPlatformTxId']] = $player_bet;
        }

        foreach ($bets as $bet) {

            $player_bet = $valid_bets($bet['refPlatformTxId']);

            $player_bet->refresh();

            $player = $player_configs->get($bet['userId'])->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::FREE_SPIN->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $locked_wallet->credit($bet['winAmount']);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                $bet['winAmount'],
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_TYPE_FREE_SPIN,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $player_balance_history->gameActionSuccess(
                $bet['winAmount'],
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_TYPE_FREE_SPIN
            );
        }

        return new AWCSeamlessResponseDTO(self::successResponse(null), 200);
    }

    private static function processGive($bets, $requested_currency): AWCSeamlessResponseDTO
    {
        $user_ids = collect($bets)->pluck('userId')->unique();

        $player_configs = AWCProviderConfig::userIdIn($user_ids)->with('player')->get()->keyBy('user_id');

        $game_codes = collect($bets)->pluck('gameCode')->unique();
        $game_items = GameItem::whereIn('game_id', $game_codes)->get()->keyBy('game_id');

        $game_item = GameItem::where('game_id', GamePlatformConstants::AWC_GAME_CODE_AESEXY_LOBBY)->first();

        foreach ($bets as $bet) {

            $player = $player_configs->get($bet['userId'])?->player;

            if (!$player) {
                return new AWCSeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            if (!$game_item) {

                return new AWCSeamlessResponseDTO(self::gameCodeNotSupportedResponse(), 200);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::GIVE->value),
                null,
                $game_item->gamePlatform->id
            );

            $bet_currency = AWCCurrencyEnums::tryFrom($bet['currency']);

            if (($player_game_currency != $bet_currency)) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    $bet['amount'],
                    false,
                    self::STATUS_DESCRIPTION_INVALID_BET,
                );

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }
        }

        foreach ($bets as $bet) {

            $player = $player_configs->get($bet['userId'])->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::GIVE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $locked_wallet->credit($bet['amount']);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                $bet['amount'],
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_TYPE_PROMOTION,
            );

            $player_balance_history->gameActionSuccess(
                $bet['amount'],
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_TYPE_PROMOTION
            );
        }

        return new AWCSeamlessResponseDTO(self::successResponse(null), 200);
    }

    private static function processCancelBetNSettle($game_item, $bets, $player, $locked_wallet, $player_game_currency): AWCSeamlessResponseDTO
    {
        foreach ($bets as $bet) {

            $credit_game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::CANCEL_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $credit_player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $debit_game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::CANCEL_BET_N_SETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $debit_player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $player_bet = $game_item->bets()->status(BetConstants::STATUS_SETTLED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->first();

            if (!$player_bet) {

                $is_duplicated = $game_item->bets()->status(BetConstants::STATUS_CANCELED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->exists();

                if (!$is_duplicated) {

                    $debit_game_transaction_history->gameActionSuccess(
                        GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                        0,
                        true,
                        $locked_wallet->balance,
                        GameTransactionHistoryConstants::NOTE_PRECANCEL_BET_SETTLE_TRANSACTION,
                    );

                    $debit_player_balance_history->gameActionSuccess(
                        0,
                        true,
                        $locked_wallet->balance,
                        $debit_game_transaction_history->id,
                        GameTransactionHistoryConstants::NOTE_PRECANCEL_BET_SETTLE_TRANSACTION
                    );
                }

                continue;
            }

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $locked_wallet->credit($player_bet->bet_amount);

            $credit_game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $player_bet->bet_amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $credit_player_balance_history->gameActionSuccess(
                $player_bet->bet_amount,
                false,
                $locked_wallet->balance,
                $debit_game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION
            );
            //

            $locked_wallet->debit($player_bet->win_amount);

            $timestamp = Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString();

            $player_bet->cancel($timestamp);

            $bet_round = $player_bet->betRound;

            $round_win_loss = $bet_round->win_loss - $player_bet->win_loss;

            $total_turnovers = $bet_round->total_turnovers - $player_bet->turnover;

            $total_valid_bets = $bet_round->total_valid_bets - $player_bet->valid_bet;

            $total_win_amount = $bet_round->total_win_amount - $player_bet->win_amount;

            $bet_round->adjust($round_win_loss, $bet_round->ended_on, $total_turnovers, $total_valid_bets, $total_win_amount);

            $debit_game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $player_bet->win_amount,
                true,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION,
                null,
                $player_bet->id,
                $credit_game_transaction_history->id,
            );

            $debit_player_balance_history->gameActionSuccess(
                $player_bet->win_amount,
                true,
                $locked_wallet->balance,
                $debit_game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION
            );
        }

        return new AWCSeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function processBetNSettle($game_item, $bets, $player, $locked_wallet, $player_game_currency): AWCSeamlessResponseDTO
    {
        $user_total_bets = array_sum(array_column($bets, 'betAmount'));

        $accumulated_bet_amount = 0;

        $valid_bets = [];

        foreach ($bets as $bet) {

            $is_duplicated = $game_item->bets()->status(BetConstants::STATUS_SETTLED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->exists();

            if ($is_duplicated) {

                continue;
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::PLACE_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $bet_currency = AWCCurrencyEnums::tryFrom($bet['currency']);

            if (($player_game_currency != $bet_currency)) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                    $user_total_bets,
                    true,
                    self::STATUS_DESCRIPTION_INVALID_CURRENCY,
                );

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            $accumulated_bet_amount += $bet['requireAmount'];

            if ($locked_wallet->balance < $accumulated_bet_amount) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                    $user_total_bets,
                    true,
                    self::STATUS_DESCRIPTION_INSUFFICIENT_FUNDS,
                );

                return new AWCSeamlessResponseDTO(self::insufficientFundsResponse(), 200);
            }

            $valid_bets[] = $bet;
        }

        foreach ($valid_bets as $bet) {

            $debit_amount = $bet['betAmount'];

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::PLACE_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $cancel_reference = self::generateReference($bet['platformTxId'], AWCActionEnums::CANCEL_BET_N_SETTLE->value);

            $canceled_transaction = $game_item->gameTransactionHistories()
                ->referenceNo($cancel_reference)
                ->status(GameTransactionHistoryConstants::STATUS_SUCCESS)
                ->playerId($player->id)
                ->first();

            $round_reference = self::generateReference($bet['roundId'], $bet['platform']);

            $bet_round = BetRound::roundReference($round_reference)->gamePlatformId($game_item->gamePlatform->id)->playerId($player->id)->first();

            if (!$bet_round) {

                $bet_round = BetRound::begin(
                    $player->id,
                    $game_item->gamePlatform->id,
                    $round_reference,
                    Carbon::parse($bet['betTime'])->setTimezone('UTC')->toDateTimeString(),
                    $locked_wallet->currency,
                    null,
                    $bet['platform']
                );
            }

            $player_bet = Bet::place(
                $debit_amount,
                $bet['roundId'],
                $bet['platformTxId'],
                $bet_round->id,
                $game_item->id,
                Carbon::parse($bet['betTime'])->setTimezone('UTC')->toDateTimeString(),
                $locked_wallet->currency,
                $bet['gameInfo']['odds'] ?? null
            );

            $refer_transaction_id = null;

            if ($canceled_transaction) {

                $refer_transaction_id = $canceled_transaction->id;
            } else {

                $locked_wallet->debit($debit_amount);
            }

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $canceled_transaction ? 0 : $debit_amount,
                true,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET,
                null,
                $player_bet->id,
                $refer_transaction_id
            );

            $player_balance_history->gameActionSuccess(
                $canceled_transaction ? 0 : $debit_amount,
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
                self::generateReference($bet['platformTxId'], AWCActionEnums::BET_N_SETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $settle_player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $odds = $bet['gameInfo']['odds'] ?? null;

            $win_loss = $bet['winAmount'] - $player_bet->bet_amount;

            $timestamp = Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString();

            $player_bet->settle(
                $bet['winAmount'],
                $timestamp,
                null,
                null,
                $bet['turnover'],
                $bet['turnover'],
                $odds,
                $win_loss
            );

            $round_win_loss = $bet_round->win_loss ? $bet_round->win_loss + $win_loss : $win_loss;

            $total_turnovers = $bet_round->total_turnovers ? $bet_round->total_turnovers + $bet['turnover'] : $bet['turnover'];

            $total_valid_bets = $bet_round->total_valid_bets ? $bet_round->total_valid_bets + $bet['turnover'] : $bet['turnover'];

            $total_win_amount = $bet_round->total_win_amount ? $bet_round->total_win_amount + $bet['winAmount'] : $bet['winAmount'];

            $bet_round->close(
                $timestamp,
                $round_win_loss,
                $total_turnovers,
                $total_valid_bets,
                $total_win_amount
            );

            if ($canceled_transaction) {

                $player_bet->cancel($timestamp);

                $round_win_loss = $bet_round->win_loss - $player_bet->win_loss;

                $total_turnovers = $bet_round->total_turnovers - $player_bet->turnover;

                $total_valid_bets = $bet_round->total_valid_bets - $player_bet->valid_bet;

                $total_win_amount = $bet_round->total_win_amount - $player_bet->win_amount;

                $bet_round->adjust($round_win_loss, $bet_round->ended_on, $total_turnovers, $total_valid_bets, $total_win_amount);
            } else {

                $locked_wallet->credit($bet['winAmount']);
            }

            $settle_game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                $canceled_transaction ? 0 : $bet['winAmount'],
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION,
                null,
                $player_bet->id,
                $game_transaction_history->id,
            );

            $settle_player_balance_history->gameActionSuccess(
                $canceled_transaction ? 0 : $bet['winAmount'],
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION
            );
        }

        return new AWCSeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function processUnvoidSettle($bets, $requested_currency): AWCSeamlessResponseDTO
    {
        $user_ids = collect($bets)->pluck('userId')->unique();

        $player_configs = AWCProviderConfig::userIdIn($user_ids)->with('player')->get()->keyBy('user_id');

        $game_codes = collect($bets)->pluck('gameCode')->unique();
        $game_items = GameItem::whereIn('game_id', $game_codes)->get()->keyBy('game_id');

        $game_item = GameItem::where('game_id', GamePlatformConstants::AWC_GAME_CODE_AESEXY_LOBBY)->first();

        $valid_bets = [];

        foreach ($bets as $bet) {

            $player = $player_configs->get($bet['userId'])?->player;

            if (!$player) {
                return new AWCSeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            if (!$game_item) {

                return new AWCSeamlessResponseDTO(self::gameCodeNotSupportedResponse(), 200);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::UNVOID_SETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_bet = $game_item->bets()->status(BetConstants::STATUS_CANCELED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->first();

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    0,
                    false,
                    self::STATUS_DESCRIPTION_INVALID_BET,
                );

                return new AWCSeamlessResponseDTO(self::invalidBetResponse(), 200);
            }

            $valid_bets[] = [
                'bet' => $bet,
                'player_bet' => $player_bet,
            ];
        }

        foreach ($valid_bets as $valid_bet) {

            $bet = $valid_bet['bet'];

            $player_bet = $valid_bet['player_bet'];

            $player_bet->refresh();

            $player = $player_configs->get($bet['userId'])->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::UNVOID_SETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $amount = null;

            $type = null;

            $is_withdraw = null;

            if ($bet['voidType'] == self::VOID_TYPE_CHEAT) {

                $amount = $player_bet->win_amount ?? 0;

                $locked_wallet->credit($amount);

                $type = GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT;

                $is_withdraw = true;
            } elseif ($bet['voidType'] == self::VOID_TYPE_GAME) {

                $is_withdraw = $player_bet->win_loss < 0;

                $amount = abs($player_bet->win_loss);

                $is_withdraw ? $locked_wallet->debit($amount) : $locked_wallet->credit($amount);

                $type = $is_withdraw ? GameTransactionHistoryConstants::TRANSACTION_TYPE_DEBIT : GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT;
            }

            $timestamp = Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString();

            $player_bet->settle($player_bet->win_amount, $timestamp);

            $bet_round = $player_bet->betRound;

            $round_win_loss = $bet_round->win_loss + $player_bet->win_loss;

            $total_turnovers = $bet_round->total_turnovers + $player_bet->win_loss;

            $total_valid_bets = $bet_round->total_valid_bets + $player_bet->valid_bet;

            $total_win_amount = $bet_round->total_win_amount + $player_bet->win_amount;

            $bet_round->close($timestamp, $round_win_loss, $total_turnovers, $total_valid_bets, $total_win_amount);

            $game_transaction_history->gameActionSuccess(
                $type,
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $player_balance_history->gameActionSuccess(
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_SETTLE_TRANSACTION
            );
        }

        return new AWCSeamlessResponseDTO(self::successResponse(null), 200);
    }

    private static function processVoidSettle($bets, $requested_currency): AWCSeamlessResponseDTO
    {
        $user_ids = collect($bets)->pluck('userId')->unique();

        $player_configs = AWCProviderConfig::userIdIn($user_ids)->with('player')->get()->keyBy('user_id');

        $game_codes = collect($bets)->pluck('gameCode')->unique();
        $game_items = GameItem::whereIn('game_id', $game_codes)->get()->keyBy('game_id');

        $game_item = GameItem::where('game_id', GamePlatformConstants::AWC_GAME_CODE_AESEXY_LOBBY)->first();

        $valid_bets = [];

        foreach ($bets as $bet) {

            $player = $player_configs->get($bet['userId'])?->player;

            if (!$player) {
                return new AWCSeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            if (!$game_item) {

                return new AWCSeamlessResponseDTO(self::gameCodeNotSupportedResponse(), 200);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::VOID_SETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $is_duplicated = $game_item->bets()->status(BetConstants::STATUS_CANCELED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->exists();

            if ($is_duplicated) {

                continue;
            }

            $player_bet = $game_item->bets()->status(BetConstants::STATUS_SETTLED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->first();

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                    0,
                    true,
                    self::STATUS_DESCRIPTION_INVALID_BET,
                );

                return new AWCSeamlessResponseDTO(self::invalidBetResponse(), 200);
            }

            $valid_bets[] = [
                'bet' => $bet,
                'player_bet' => $player_bet,
            ];
        }

        foreach ($valid_bets as $valid_bet) {

            $bet = $valid_bet['bet'];

            $player_bet = $valid_bet['player_bet'];

            $player_bet->refresh();

            $player = $player_configs->get($bet['userId'])->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::VOID_SETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $amount = null;

            $note = null;

            $is_withdraw = null;

            if ($bet['voidType'] == self::VOID_TYPE_CHEAT) {

                $amount = $player_bet->win_amount ?? 0;

                $locked_wallet->debit($amount);

                $note = GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION_PLAYER_CHEATED;

                $is_withdraw = true;
            } elseif ($bet['voidType'] == self::VOID_TYPE_GAME) {

                $is_withdraw = $player_bet->win_loss > 0;

                $amount = abs($player_bet->win_loss);

                $is_withdraw ? $locked_wallet->debit($amount) : $locked_wallet->credit($amount);

                $note = GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION;
            }

            $timestamp = Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString();

            $player_bet->cancel($timestamp);

            $bet_round = $player_bet->betRound;

            $round_win_loss = $bet_round->win_loss - $player_bet->win_loss;

            $total_turnovers = $bet_round->total_turnovers - $player_bet->turnover;

            $total_valid_bets = $bet_round->total_valid_bets - $player_bet->valid_bet;

            $total_win_amount = $bet_round->total_win_amount - $player_bet->win_amount;

            $bet_round->adjust($round_win_loss, $bet_round->ended_on, $total_turnovers, $total_valid_bets, $total_win_amount);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                $note,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $player_balance_history->gameActionSuccess(
                $amount,
                $is_withdraw,
                $locked_wallet->balance,
                $game_transaction_history->id,
                $note
            );
        }

        return new AWCSeamlessResponseDTO(self::successResponse(null), 200);
    }

    private static function processUnsettle($bets, $requested_currency): AWCSeamlessResponseDTO
    {
        $user_ids = collect($bets)->pluck('userId')->unique();

        $player_configs = AWCProviderConfig::userIdIn($user_ids)->with('player')->get()->keyBy('user_id');

        $game_codes = collect($bets)->pluck('gameCode')->unique();
        $game_items = GameItem::whereIn('game_id', $game_codes)->get()->keyBy('game_id');

        $game_item = GameItem::where('game_id', GamePlatformConstants::AWC_GAME_CODE_AESEXY_LOBBY)->first();

        $valid_bets = [];

        foreach ($bets as $bet) {

            $player = $player_configs->get($bet['userId'])?->player;

            if (!$player) {
                return new AWCSeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            if (!$game_item) {

                return new AWCSeamlessResponseDTO(self::gameCodeNotSupportedResponse(), 200);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::UNSETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_bet = $game_item->bets()->status(BetConstants::STATUS_SETTLED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->first();

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                    0,
                    true,
                    self::STATUS_DESCRIPTION_INVALID_BET,
                );

                return new AWCSeamlessResponseDTO(self::invalidBetResponse(), 200);
            }

            $valid_bets[] = [
                'bet' => $bet,
                'player_bet' => $player_bet,
            ];
        }

        foreach ($valid_bets as $valid_bet) {

            $bet = $valid_bet['bet'];

            $player_bet = $valid_bet['player_bet'];

            $player_bet->refresh();

            $player = $player_configs->get($bet['userId'])->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::UNSETTLE->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $locked_wallet->debit($player_bet->win_amount);

            $bet_round = $player_bet->betRound;

            $round_win_loss = $bet_round->win_loss - $player_bet->win_loss;

            $total_turnovers = $bet_round->total_turnovers - $player_bet->turnover;

            $total_valid_bets = $bet_round->total_valid_bets - $player_bet->valid_bet;

            $total_win_amount = $bet_round->total_win_amount - $player_bet->win_amount;

            $player_bet->unsettle();

            $bet_round->reopen($round_win_loss, $total_turnovers, $total_valid_bets, $total_win_amount);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_UNSETTLE,
                $player_bet->win_amount,
                true,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_TYPE_UNSETTLE_BET,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $player_balance_history->gameActionSuccess(
                $player_bet->win_amount,
                true,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_TYPE_UNSETTLE_BET
            );
        }

        return new AWCSeamlessResponseDTO(self::successResponse(null), 200);
    }

    private static function processRefund($game_item, $bets, $player, $locked_wallet, $player_game_currency): AWCSeamlessResponseDTO
    {
        $bet_references = array_column($bets, 'refundPlatformTxId');

        $is_same_bet_reference = count(array_unique($bet_references)) === 1;

        $bet_reference = $is_same_bet_reference ? $bet_references[0] : null;

        $player_bet = $game_item->bets()->reference($bet_reference)->playerId($locked_wallet->player_id)->first();

        $game_transaction_history = GameTransactionHistory::gameAction(
            $locked_wallet->balance,
            $player->id,
            $locked_wallet->currency,
            $locked_wallet->id,
            $game_item->id,
            self::generateReference($bet_reference, AWCActionEnums::REFUND->value),
            null,
            $game_item->gamePlatform->id
        );

        $player_balance_history = PlayerBalanceHistory::gameAction(
            $player->id,
            $locked_wallet->balance,
            $locked_wallet->currency,
        );

        if (count($bets) !== 2) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_REFUND,
                0,
                false,
                self::STATUS_DESCRIPTION_INVALID_BET,
            );

            return new AWCSeamlessResponseDTO(self::invalidBetResponse(), 200);
        }

        if (!$player_bet) {

            $game_transaction_history->gameActionFailed(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_REFUND,
                0,
                false,
                self::STATUS_DESCRIPTION_INVALID_BET,
            );

            return new AWCSeamlessResponseDTO(self::invalidBetResponse(), 200);
        }

        foreach ($bets as $bet) {

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::REFUND->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refund_amount = $bet['winAmount'] - $bet['betAmount'];

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $locked_wallet->credit($refund_amount);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_REFUND,
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

        return new AWCSeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function processUnvoidBet($bets, $requested_currency): AWCSeamlessResponseDTO
    {
        $user_ids = collect($bets)->pluck('userId')->unique();

        $player_configs = AWCProviderConfig::userIdIn($user_ids)->with('player')->get()->keyBy('user_id');

        $game_codes = collect($bets)->pluck('gameCode')->unique();
        $game_items = GameItem::whereIn('game_id', $game_codes)->get()->keyBy('game_id');

        $game_item = GameItem::where('game_id', GamePlatformConstants::AWC_GAME_CODE_AESEXY_LOBBY)->first();

        $valid_bets = [];

        foreach ($bets as $bet) {

            $player = $player_configs->get($bet['userId'])?->player;

            if (!$player) {
                return new AWCSeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            if (!$game_item) {

                return new AWCSeamlessResponseDTO(self::gameCodeNotSupportedResponse(), 200);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::UNVOID_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_bet = $game_item->bets()->status(BetConstants::STATUS_CANCELED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->first();

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                    0,
                    true,
                    self::STATUS_DESCRIPTION_INVALID_BET,
                );

                return new AWCSeamlessResponseDTO(self::invalidBetResponse(), 200);
            }

            $valid_bets[] = [
                'bet' => $bet,
                'player_bet' => $player_bet,
            ];
        }

        foreach ($valid_bets as $valid_bet) {

            $bet = $valid_bet['bet'];

            $player_bet = $valid_bet['player_bet'];

            $player_bet->refresh();

            $player = $player_configs->get($bet['userId'])->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::UNVOID_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $locked_wallet->debit($player_bet->bet_amount);

            $player_bet->unsettle();

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $player_bet->bet_amount,
                true,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $player_balance_history->gameActionSuccess(
                $player_bet->bet_amount,
                true,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET
            );
        }

        return new AWCSeamlessResponseDTO(self::successResponse(null), 200);
    }

    private static function processVoidBet($bets, $requested_currency): AWCSeamlessResponseDTO
    {
        $user_ids = collect($bets)->pluck('userId')->unique();

        $player_configs = AWCProviderConfig::userIdIn($user_ids)->with('player')->get()->keyBy('user_id');

        $game_codes = collect($bets)->pluck('gameCode')->unique();
        $game_items = GameItem::whereIn('game_id', $game_codes)->get()->keyBy('game_id');

        $game_item = GameItem::where('game_id', GamePlatformConstants::AWC_GAME_CODE_AESEXY_LOBBY)->first();

        $valid_bets = [];

        foreach ($bets as $bet) {

            $player = $player_configs->get($bet['userId'])?->player;

            if (!$player) {
                return new AWCSeamlessResponseDTO(self::userNotFoundResponse(), 200);
            }

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);

            if (($player_game_currency != $requested_currency)) {

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            if (!$game_item) {

                return new AWCSeamlessResponseDTO(self::gameCodeNotSupportedResponse(), 200);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::VOID_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $is_duplicated = $game_item->bets()->status(BetConstants::STATUS_CANCELED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->exists();

            if ($is_duplicated) {

                continue;
            }

            $player_bet = $game_item->bets()->status(BetConstants::STATUS_UNSETTLED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->first();

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                    $bet['betAmount'],
                    false,
                    self::STATUS_DESCRIPTION_INVALID_BET,
                );

                return new AWCSeamlessResponseDTO(self::invalidBetResponse(), 200);
            }

            $valid_bets[] = [
                'bet' => $bet,
                'player_bet' => $player_bet,
            ];
        }

        foreach ($valid_bets as $valid_bet) {

            $bet = $valid_bet['bet'];

            $player_bet = $valid_bet['player_bet'];

            $player_bet->refresh();

            $player = $player_configs->get($bet['userId'])->player;

            $locked_wallet = $player->wallet()->lockForUpdate()->first();

            if ($bet['platform'] != GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT) {

                $game_item = $game_items->get($bet['gameCode']);
            }

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::VOID_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $locked_wallet->credit($player_bet->bet_amount);

            $timestamp = Carbon::parse($bet['updateTime'])->setTimezone('UTC')->toDateTimeString();

            $player_bet->cancel($timestamp);

            $bet_winloss = $player_bet->win_loss ?? $player_bet->win_amount - $player_bet->bet_amount;

            $bet_round = $player_bet->betRound;

            $winloss = $bet_round->win_loss - $bet_winloss;

            $bet_round->adjust($winloss, $bet_round->ended_on);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                $player_bet->bet_amount,
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $player_balance_history->gameActionSuccess(
                $player_bet->bet_amount,
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_CANCEL_TRANSACTION
            );
        }

        return new AWCSeamlessResponseDTO(self::successResponse(null), 200);
    }

    private static function processAdjustBet($game_item, $bets, $player, $locked_wallet, $player_game_currency): AWCSeamlessResponseDTO
    {
        $bet_references = collect($bets)->pluck('platformTxId')->unique();

        $player_bets = $game_item->bets()->status(BetConstants::STATUS_UNSETTLED)->referenceIn($bet_references)->playerId($locked_wallet->player_id)->get()->keyBy('bet_reference');

        foreach ($bets as $bet) {

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::ADJUST_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_bet = $player_bets->get($bet['platformTxId']);

            if (!$player_bet) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ADJUST,
                    $bet['adjustAmount'],
                    false,
                    self::STATUS_DESCRIPTION_INVALID_BET,
                );

                return new AWCSeamlessResponseDTO(self::invalidBetResponse(), 200);
            }
        }

        foreach ($bets as $bet) {

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::ADJUST_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $player_bet = $player_bets->get($bet['platformTxId']);

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

            $locked_wallet->credit($bet['adjustAmount']);

            $odds = $bet['gameInfo']['odds'] ?? $player_bet->odds;

            $player_bet->adjust($bet['betAmount'], $player_bet->valid_bet, $player_bet->turnover, $odds);

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_ADJUST,
                $bet['adjustAmount'],
                false,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION,
                null,
                $player_bet->id,
                $refer_transaction->id,
            );

            $player_balance_history->gameActionSuccess(
                $bet['adjustAmount'],
                false,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION
            );
        }

        return new AWCSeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function processCancelBet($game_item, $bets, $player, $locked_wallet, $player_game_currency): AWCSeamlessResponseDTO
    {
        foreach ($bets as $bet) {

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::CANCEL_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $player_bet = $game_item->bets()->status(BetConstants::STATUS_UNSETTLED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->first();

            if (!$player_bet) {

                $is_duplicated = $game_item->bets()->status(BetConstants::STATUS_CANCELED)->reference($bet['platformTxId'])->playerId($locked_wallet->player_id)->exists();

                if (!$is_duplicated) {

                    $game_transaction_history->gameActionSuccess(
                        GameTransactionHistoryConstants::TRANSACTION_TYPE_CANCEL,
                        0,
                        false,
                        $locked_wallet->balance,
                        GameTransactionHistoryConstants::NOTE_PRECANCEL_TRANSACTION,
                    );

                    $player_balance_history->gameActionSuccess(
                        0,
                        false,
                        $locked_wallet->balance,
                        $game_transaction_history->id,
                        GameTransactionHistoryConstants::NOTE_PRECANCEL_TRANSACTION
                    );
                }

                continue;
            }

            $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

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

        return new AWCSeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function processPlaceBet($game_item, $bets, $player, $locked_wallet, $player_game_currency): AWCSeamlessResponseDTO
    {
        $user_total_bets = array_sum(array_column($bets, 'betAmount'));

        $accumulated_bet_amount = 0;

        foreach ($bets as $bet) {

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::PLACE_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $bet_currency = AWCCurrencyEnums::tryFrom($bet['currency']);

            if (($player_game_currency != $bet_currency)) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                    $user_total_bets,
                    true,
                    self::STATUS_DESCRIPTION_INVALID_CURRENCY,
                );

                return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
            }

            $accumulated_bet_amount += $bet['betAmount'];

            if ($locked_wallet->balance < $accumulated_bet_amount) {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                    $user_total_bets,
                    true,
                    self::STATUS_DESCRIPTION_INSUFFICIENT_FUNDS,
                );

                return new AWCSeamlessResponseDTO(self::insufficientFundsResponse(), 200);
            }
        }

        foreach ($bets as $bet) {

            $debit_amount = $bet['betAmount'];

            $game_transaction_history = GameTransactionHistory::gameAction(
                $locked_wallet->balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                self::generateReference($bet['platformTxId'], AWCActionEnums::PLACE_BET->value),
                null,
                $game_item->gamePlatform->id
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $locked_wallet->balance,
                $locked_wallet->currency,
            );

            $cancel_reference = self::generateReference($bet['platformTxId'], AWCActionEnums::CANCEL_BET->value);

            $canceled_transaction = $game_item->gameTransactionHistories()
                ->referenceNo($cancel_reference)
                ->status(GameTransactionHistoryConstants::STATUS_SUCCESS)
                ->playerId($player->id)
                ->first();;

            $round_reference = self::generateReference($bet['roundId'], $bet['platform']);

            $bet_round = BetRound::roundReference($round_reference)->gamePlatformId($game_item->gamePlatform->id)->playerId($player->id)->first();

            if (!$bet_round) {

                $bet_round = BetRound::begin(
                    $player->id,
                    $game_item->gamePlatform->id,
                    $round_reference,
                    Carbon::parse($bet['betTime'])->setTimezone('UTC')->toDateTimeString(),
                    $locked_wallet->currency,
                    null,
                    $bet['platform']
                );
            }

            $player_bet = Bet::place(
                $debit_amount,
                $bet['roundId'],
                $bet['platformTxId'],
                $bet_round->id,
                $game_item->id,
                Carbon::parse($bet['betTime'])->setTimezone('UTC')->toDateTimeString(),
                $locked_wallet->currency,
                $bet['gameInfo']['odds'] ?? null,
                null,
                null,
                $bet['gameCode']
            );

            $refer_transaction_id = null;

            if ($canceled_transaction) {

                $player_bet->cancel(now()->toDateTimeString());

                $refer_transaction_id = $canceled_transaction->id;
            } else {

                $locked_wallet->debit($debit_amount);
            }

            $game_transaction_history->gameActionSuccess(
                GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                $canceled_transaction ? 0 : $debit_amount,
                true,
                $locked_wallet->balance,
                GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET,
                null,
                $player_bet->id,
                $refer_transaction_id
            );

            $player_balance_history->gameActionSuccess(
                $canceled_transaction ? 0 : $debit_amount,
                true,
                $locked_wallet->balance,
                $game_transaction_history->id,
                GameTransactionHistoryConstants::NOTE_PLYER_PLACED_BET
            );
        }

        return new AWCSeamlessResponseDTO(self::successResponse($locked_wallet->balance), 200);
    }

    private static function processGetBalance($data, $requested_currency)
    {
        $player_config = AWCProviderConfig::userId($data['userId'])->first();

        if (!$player_config) {
            return new AWCSeamlessResponseDTO(self::userNotFoundResponse(), 200);
        }

        $player = $player_config->player;
        $locked_wallet = $player->wallet()->lockForUpdate()->first();
        $balance = $locked_wallet->balance;

        $player_game_currency = self::getGameCurrency($locked_wallet->currency);

        if ($player_game_currency != $requested_currency) {
            return new AWCSeamlessResponseDTO(self::invalidCurrencyResponse(), 200);
        }

        return new AWCSeamlessResponseDTO(self::getBalanceSuccessResponse($data['userId'], $balance), 200);
    }

    //responses

    private static function gameCodeNotSupportedResponse()
    {
        return [
            'status' => self::STATUS_CODE_INVALID_GAME,
            'desc' => self::STATUS_DESCRIPTION_INVALID_GAME,
        ];
    }

    private static function invalidSettleTypeResponse()
    {
        return [
            'status' => self::STATUS_CODE_INVALID_PARAMETER,
            'desc' => self::STATUS_DESCRIPTION_INVALID_SETTLE_TYPE,
        ];
    }

    private static function invalidBetResponse()
    {
        return [
            'status' => self::STATUS_CODE_INVALID_BET,
            'desc' => self::STATUS_DESCRIPTION_INVALID_BET,
        ];
    }

    private static function insufficientFundsResponse()
    {
        return [
            'status' => self::STATUS_CODE_INSUFFICIENT_FUNDS,
            'desc' => self::STATUS_DESCRIPTION_INSUFFICIENT_FUNDS,
        ];
    }

    private static function successResponse($balance)
    {
        return [
            'status' => self::STATUS_CODE_SUCCESS,
            'balance' => self::RoundBalance($balance),
            'balanceTs' => now()->format('Y-m-d\TH:i:s.vP'),
        ];
    }

    private static function actionNotSupported()
    {
        return [
            'status' => self::STATUS_CODE_INVALID_PARAMETER,
            'desc' => self::STATUS_DESCRIPTION_ACTION_NOT_SUPPORTED,
        ];
    }

    private static function getBalanceSuccessResponse($user_id, $balance)
    {
        return [
            'status' => self::STATUS_CODE_SUCCESS,
            'userId' => $user_id,
            'balance' => self::RoundBalance($balance),
            'balanceTs' => now()->format('Y-m-d\TH:i:s.vP'),
        ];
    }

    private static function invalidCurrencyResponse()
    {
        return [
            'status' => self::STATUS_CODE_INVALID_CURRENCY,
            'desc' => self::STATUS_DESCRIPTION_INVALID_CURRENCY,
        ];
    }

    private static function userNotFoundResponse()
    {
        return [
            'status' => self::STATUS_CODE_INVALID_USER_ID,
            'desc' => self::STATUS_DESCRIPTION_INVALID_USER_ID,
        ];
    }

    private static function ipNotAllowedResponse($ip)
    {
        return [
            'status' => self::STATUS_CODE_INVALID_IP_ADDRESS,
            'desc' => self::STATUS_DESCRIPTION_INVALID_IP_ADDRESS,
            'ip' => $ip,
        ];
    }

    private static function authFailedResponse()
    {
        return [
            'status' => self::STATUS_CODE_AUTH_FAILED,
            'desc' => self::STATUS_DESCRIPTION_AUTH_FAILED,
        ];
    }

    private static function unknownErrorResponse()
    {
        return [
            'status' => self::STATUS_CODE_UNKNOWN_ERROR,
            'desc' => self::STATUS_DESCRIPTION_UNKNOWN_ERROR,
        ];
    }

    private static function validationErrorResponse($error)
    {
        return [
            'status' => self::STATUS_CODE_INVALID_PARAMETER,
            'desc' => self::STATUS_DESCRIPTION_INVALID_PARAMETER,
            'parameter' => $error,
        ];
    }
}
