<?php

namespace App\Services\Providers\GeminiProvider;

use App\Constants\GamePlatformConstants;
use App\Constants\GameTransactionHistoryConstants;
use Exception;
use App\Models\Player;
use App\Constants\GlobalConstants;
use App\Models\Bet;
use App\Models\BetRound;
use App\Models\GameItem;
use App\Models\GameTransactionHistory;
use App\Models\PlayerBalanceHistory;
use App\Models\User;
use App\Services\Providers\GeminiProvider\DTOs\GeminiSeamlessResponseDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\Providers\GeminiProvider\Enums\GeminiCurrencyEnums;
use App\Services\Providers\ProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;
// 
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\HasApiTokens;



class GeminiProvider implements ProviderInterface
{
    use HasApiTokens;
    //status codes 
    const STATUS_CODE_SUCCESS                       =   0;
    const STATUS_CODE_UNSPECIFIED_ERROR             =   11000;
    const STATUS_CODE_INVALID_TOKEN                 =   11001;
    const STATUS_CODE_EXPIRED_TOKEN                 =   11002;
    const STATUS_CODE_INCORRECT_AUTHENTICATION      =   11003;
    const STATUS_CODE_INCORRECT_PARAMETER           =   11004;
    const STATUS_CODE_DUPLICATE_REQUEST             =   11005;
    const STATUS_CODE_SEQUENCE_NOT_EXISTS           =   11006;
    const STATUS_CODE_REQUEST_WAS_NOT_ALLOWED       =   11007;
    const STATUS_CODE_DUPLICATE_TRANSFER_NO         =   11008;
    const STATUS_CODE_TRANSFER_NO_NOT_EXISTS        =   11009;
    const STATUS_CODE_LOGIN_FAILED                  =   12001;
    const STATUS_CODE_ACCOUNT_WAS_LOCKED            =   12002;
    const STATUS_CODE_ACCOUNT_NOT_EXISTS            =   12003;
    const STATUS_CODE_ACCOUNT_WAS_BLACKLISTED       =   12004;
    const STATUS_CODE_ACCOUNT_EXISTS                =   12005;
    const STATUS_CODE_PLAYER_HAS_INSUFFICIENT_FUNDS =   13001;
    const STATUS_CODE_INVALID_IP                    =   13002;
    const STATUS_CODE_TIMEOUT                       =   13003;
    const STATUS_CODE_ORDER_NOT_EXISTS              =   14001;
    const STATUS_CODE_UNDER_MAINTENANCE             =   15001;

    //status description 
    const STATUS_DESC_SUCCESS                       =   'Success';
    const STATUS_DESC_UNSPECIFIED_ERROR             =   'UnspecifiedError';
    const STATUS_DESC_INVALID_TOKEN                 =   'InvalidToken';
    const STATUS_DESC_EXPIRED_TOKEN                 =   'ExpiredToken';
    const STATUS_DESC_INCORRECT_AUTHENTICATION      =   'IncorrectAuthentication';
    const STATUS_DESC_INCORRECT_PARAMETER           =   'IncorrectParameter';
    const STATUS_DESC_DUPLICATE_REQUEST             =   'DuplicateRequest';
    const STATUS_DESC_SEQUENCE_NOT_EXISTS           =   'SequenceNotExists';
    const STATUS_DESC_REQUEST_WAS_NOT_ALLOWED       =   'RequestWasNotAllowed';
    const STATUS_DESC_DUPLICATE_TRANSFER_NO         =   'DuplicateTransferNo';
    const STATUS_DESC_TRANSFER_NO_NOT_EXISTS        =   'TransferNoNotExists';
    const STATUS_DESC_LOGIN_FAILED                  =   'LoginFailed';
    const STATUS_DESC_ACCOUNT_WAS_LOCKED            =   'AccountWasLocked';
    const STATUS_DESC_ACCOUNT_NOT_EXISTS            =   'AccountNotExists';
    const STATUS_DESC_ACCOUNT_WAS_BLACKLISTED       =   'AccountWasBlacklisted';
    const STATUS_DESC_ACCOUNT_EXISTS                =   'AccountExists';
    const STATUS_DESC_PLAYER_HAS_INSUFFICIENT_FUNDS =   'PlayerHasInsufficientFunds';
    const STATUS_DESC_INVALID_IP                    =   'InvalidIP';
    const STATUS_DESC_TIMEOUT                       =   'Timeout';
    const STATUS_DESC_ORDER_NOT_EXISTS              =   'OrderNotExists';
    const STATUS_DESC_UNDER_MAINTENANCE             =   'UnderMaintenance';

    // languages
    const LANG_EN   =   'en-US';
    const LANG_VN   =   'vi-VN';
    const LANG_KR   =   'ko-KR';
    const LANG_ID   =   'id-ID';
    const LANG_TH   =   'th-TH';
    const LANG_BR   =   'pt-BR';
    const LANG_JP   =   'ja-JP';
    const LANG_CN   =   'zh-CN';
    const LANG_MX   =   'es-MX';

    // operations
    const OPERATION_ADD = 'add';
    const OPERATION_SUB = 'sub';

    // Transaction Type
    const TRANSACTION_TYPE_BET      = 'BET';
    const TRANSACTION_TYPE_RECKON   = 'RECKON';
    const TRANSACTION_TYPE_ROLLBACK = 'ROLLBACK';
    const TRANSACTION_TYPE_EXTBET   = 'EXTBET';

    protected $username;
    protected $currency;
    protected $baseUrl;
    protected $pid;
    protected $secretKey;
    protected $headers;
    protected $language;
    protected $checksum; 
    protected $gameCode; 
    protected $token;
    protected $gameType; 
    protected $playerCurrency;
    protected $playerBalance;
    protected $getGroupType;

    public function __construct(Player $player,$gameType)
    {
        $this->username         =       $player->user->user_name; 
        $this->currency         =       self::getGameCurrency($player->wallet->currency);
        $credentials            =       self::getCredential($this->currency);
        $this->gameType         =       $gameType;
        $this->getGroupType     =       explode("-",$this->gameType);
        $this->getGroupType[0]  ===     'Hash' ? $this->baseUrl = $credentials['hash_api_base_url'] : $this->baseUrl  = $credentials['bingo_api_base_url'];
        $this->gameType         =       $gameType;
        $this->language         =       self::getGameLanguage($player->language);
        $this->token            =       $player->createToken('gemini-token')->plainTextToken;
        $this->pid              =       $credentials['pid'];
        $this->secretKey        =       $credentials['secret_key'];
        $this->headers          =       [
                                            'Content-Type' => 'application/json',
                                            'Accept' => 'application/json',
                                        ];
        $this->checksum         =       md5($this->secretKey . $this->pid . $this->token); 
        // TODO: get it dynamically
        $this->playerCurrency   =        $this->currency;    
        $this->playerBalance    =        $player->wallet->balance;    
    }

    public function loginToGame($language, $loginIp, $deviceType): ? string
    {
        try
        {
            $baseUrl            =   $this->baseUrl;
            $gameType           =   $this->gameType;
            $playerLanguage     =   $this->language;
            $productId          =   $this->pid;
            $token              =   $this->token;
            $checksum           =   $this->checksum;

            // main game url
            $url                =   $baseUrl."/".$gameType."/?Lang=".$playerLanguage."&ProductId=".$productId."&Token=".$token."&Checksum=".$checksum;
            $response           =   [
                                        'link' => $url,
                                        'is_url' => true,
                                        'status' => self::STATUS_DESC_SUCCESS,
                                    ];
            $requestedBody      =   [
                                        'seq' =>  "1",
                                        'product_id' =>  $productId,
                                    ];

            $encodedData = json_encode($response);
            Log::info($url);
            Log::info($encodedData);
            Log::info('Group type',[$this->getGroupType]);
            Log::info('Requested Body',[$requestedBody]);
            
            $this->headers['els-access-key'] = md5($this->secretKey.json_encode($requestedBody));

            Log::info('Requested Body',[$requestedBody]);
            return $encodedData;
        }
        catch (\Throwable $th) 
        {
            Log::info('***************************************************************************************');
            Log::info('Gemini Provider Call loginToGame API Exception');
            Log::info($th);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        return self::STATUS_DESC_ORDER_NOT_EXISTS;
    }

    public static function getCredential(GeminiCurrencyEnums $gemini_currency)
    {
        $pid            =   null;
        $auth_key       =   null;
        $secret_key     =   null;
        if($gemini_currency == GeminiCurrencyEnums::PHP)
        {
            $pid            =   Config::get('app.gemini_pid.php');
            $auth_key       =   Config::get('app.gemini_auth_key.php');
            $secret_key     =   Config::get('app.gemini_secret_key.php');
        }

        return [
            'pid'                   =>      $pid,
            'auth_key'              =>      $auth_key,
            'secret_key'            =>      $secret_key,
            'base_url'              =>      Config::get('app.gemini_base_url'),
            'bingo_api_base_url'    =>      Config::get('app.gemini_bingo_api_base_url'),
            'hash_api_base_url'     =>      Config::get('app.gemini_hash_api_base_url'),
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
    public function getResponse($sequence,$timestamp,$code,$desc,$product_id,$dataValue,$dataKey)
    {
        return response()->json([
            'seq'           =>      $sequence,
            'timestamp'     =>      $timestamp,
            'code'          =>      $code,
            'message'       =>      $desc,
            'data'          =>      [
                                        'product_id'    =>  $product_id,
                                        $dataKey       =>  $dataValue
                                    ],
        ]);
    }
    // get player info function
    public function getPlayerInfo($sequence,$timestamp,$product_id,$token)
    {
        try 
        {
            Log::info("============== get player Info Request parameters================================");
            Log::info("sequence",   [$sequence]);
            Log::info("timestamp",  [$timestamp]);
            Log::info("product_id", [$product_id]);
            Log::info("token",      [$token]);
            Log::info("================================================================");
            $userToken      =   PersonalAccessToken::findToken($token);
            $credentials    =   GeminiProvider::getCredential($this->currency);
    
            if(!$userToken->tokenable())
            {
                return self::getResponse($sequence,$timestamp,self::STATUS_CODE_INVALID_TOKEN,self::STATUS_DESC_INVALID_TOKEN,$product_id,$token,'token');
            }
    
            if($product_id != $credentials['pid'])
            {
                return self::getResponse($sequence,$timestamp,self::STATUS_CODE_INVALID_TOKEN,self::STATUS_DESC_INVALID_TOKEN,$product_id,$token,'token');
            }
    
            $response = [
                "seq"       =>  $sequence,
                "timestamp" =>  $timestamp,
                "code"      =>  self::STATUS_CODE_SUCCESS,
                "message"   =>  self::STATUS_DESC_SUCCESS,
                "data"      =>  [
                                    "product_id"    =>  $product_id,
                                    "username"      =>  $this->username,
                                    "token"         =>  $token,
                                    "currency"      =>  "php", //TODO:: change the currency
                                    // "currency"      =>  $this->currency, 
                                    "wallet"        =>  [
                                                            [
                                                                // "currency" =>$this->playerCurrency,//TODO:: change the currency
                                                                "currency" =>"php",
                                                                "balance" =>(string) $this->playerBalance
                                                            ]
                                                        ],
                                ],
                ];
    
            Log::info("==============Response parameters================================");
            Log::info("response Data",[$response]);
            Log::info("================================================================");
            return response()->json($response, 200);
        } 
        catch (\Throwable $th) 
        {
            Log::info("==============getPlayerInfo Exception ================================");
            Log::info("Exception",[$th->getMessage()]);
            Log::info("================================================================");
            return self::getResponse($sequence,$timestamp,self::STATUS_CODE_UNSPECIFIED_ERROR,self::STATUS_DESC_UNSPECIFIED_ERROR,$product_id,$token,'token');

        }
    }

    //Check the user balance 
    public function checkBalance($sequence,$timestamp,$product_id,$token,$username)
    {
        try 
        {
            Log::info("============== checkBalance Request parameters================================");
            Log::info("sequence",   [$sequence]);
            Log::info("timestamp",  [$timestamp]);
            Log::info("product_id", [$product_id]);
            Log::info("token",      [$token]);
            Log::info("================================================================");

            $userToken      =   PersonalAccessToken::findToken($token);
            $credentials    =   GeminiProvider::getCredential($this->currency);

            if(!$userToken->tokenable())
            {
                return self::getResponse($sequence,$timestamp,self::STATUS_CODE_INVALID_TOKEN,self::STATUS_DESC_INVALID_TOKEN,$product_id,$username,'username');
            }

            if($product_id != $credentials['pid'])
            {
                return self::getResponse($sequence,$timestamp,self::STATUS_CODE_INVALID_TOKEN,self::STATUS_DESC_INVALID_TOKEN,$product_id,$username,'username');
            }

            // username validation
            if($this->username !== $username)
            {
                return self::getResponse($sequence,$timestamp,self::STATUS_CODE_INCORRECT_PARAMETER,self::STATUS_DESC_INCORRECT_PARAMETER,$product_id,$username,'username');
            }

            $response = [
                "seq"       =>  $sequence,
                "timestamp" =>  $timestamp,
                "code"      =>  self::STATUS_CODE_SUCCESS,
                "message"   =>  self::STATUS_DESC_SUCCESS,
                "data"      =>  [
                                    "product_id"    =>  $product_id,
                                    "username"      =>  $username,
                                    "wallet"        =>  [
                                                            [
                                                                // "currency" =>$this->playerCurrency,//TODO:: change the currency
                                                                "currency" =>"php",
                                                                "balance" => $this->playerBalance
                                                            ]
                                                        ],
                                ],
                ];
    
            Log::info("==============checkBalance Response parameters================================");
            Log::info("response Data",[$response]);
            Log::info("================================================================");
            return response()->json($response, 200);

        } 
        catch (\Throwable $th) 
        {
            Log::info("==============checkBalance Exception ================================");
            Log::info("Exception",[$th->getMessage()]);
            Log::info("================================================================");
            return self::getResponse($sequence,$timestamp,self::STATUS_CODE_UNSPECIFIED_ERROR,self::STATUS_DESC_UNSPECIFIED_ERROR,$product_id,$username,'username');
        }        
    } 
    // "gamecode": "0D01230627175748000", //hash
    // "gamecode": "KB002183011JR", // bingo

    // TODO:: For timebeing created this function
    public function playerTransfer($sequence,$timestamp,$product_id,$token,$username,$gameType,$gameCode = null,$billNo,$transAmount = null,$transaction,$currency = null)
    {

        // !remove these logs when the functionality is ready
        Log::info("==============playerTransfer provider request parameters ================================");
        Log::info("sequence",   [$sequence]);
        Log::info("timestamp",  [$timestamp]);
        Log::info("product_id", [$product_id]);
        Log::info("token",      [$token]);
        Log::info("username",   [$username]);
        Log::info("gameType",   [$gameType]);
        Log::info("gameCode",   [$gameCode]);
        Log::info("billNo",     [$billNo]);
        Log::info("transAmount",[$transAmount]);
        Log::info("transaction",[$transaction]);
        Log::info("currency",   [$currency]);
        Log::info("=================================================================================");
        $userToken      =   PersonalAccessToken::findToken($token);
        $credentials    =   GeminiProvider::getCredential($this->currency);
        if($transAmount < 0)
        {
            $operation  =   self::OPERATION_SUB;
        }
        else
        {
            $operation  =   self::OPERATION_ADD;
        }
        $transAmount    =   abs($transAmount);
        try 
        {
             // validation
            if(!$userToken->tokenable())
            {
                Log::info("==============Token error ================================");
                return self::getResponse($sequence,$timestamp,self::STATUS_CODE_INVALID_TOKEN,self::STATUS_DESC_INVALID_TOKEN,$product_id,$username,'username');
            }

            if($product_id != $credentials['pid'])
            {
                Log::info("==============Product id error ================================");
                return self::getResponse($sequence,$timestamp,self::STATUS_CODE_INVALID_TOKEN,self::STATUS_DESC_INVALID_TOKEN,$product_id,$username,'username');
            }

            // username validation
            if($this->username !== $username)
            {
                Log::info("==============username error ================================");
                Log::info("logged in username",[$this->username]);
                Log::info("request username",[$username]);
                return self::getResponse($sequence,$timestamp,self::STATUS_CODE_INCORRECT_PARAMETER,self::STATUS_DESC_INCORRECT_PARAMETER,$product_id,$username,'username');
            }

            $user = User::where('user_name', $username)->first();

            if (!$user) {
                return self::getResponse($sequence,$timestamp,self::STATUS_CODE_ACCOUNT_NOT_EXISTS,self::STATUS_DESC_ACCOUNT_NOT_EXISTS,$product_id,$username,'username');
            }

            // ==============================

            $player = $user->player;
            $locked_wallet = $player->wallet()->lockForUpdate()->first();
            $balance = $locked_wallet->balance;

            $player_game_currency = self::getGameCurrency($locked_wallet->currency);
            
            $bet_currency = GeminiCurrencyEnums::tryFrom($currency);

            $game_item = GameItem::where('game_id',$gameType)->first();

            // currency will be null if the transaction type is rollback
            if($transaction !== self::TRANSACTION_TYPE_ROLLBACK)
            {
                if (($player_game_currency->value != $currency) || ($player_game_currency->value != $bet_currency->value)) {
                    Log::info("==============currency error ================================");
                    Log::info("player_game_currency",[$player_game_currency->value]);
                    Log::info("bet_currency ",[$bet_currency->value]);
                    Log::info("currency ",[$currency]);
                    return self::getResponse($sequence,$timestamp,self::STATUS_CODE_INCORRECT_PARAMETER,self::STATUS_DESC_INCORRECT_PARAMETER,$product_id,$username,'username');
        
                } 
            }

            
            // ======================================Validation Ends ===========================================
            // for placing bet
            Log::info("==============Outside Bet Function ================================");

            $transfer_no = Uuid::uuid4()->toString();

            $game_transaction_history = GameTransactionHistory::gameAction(
                $balance,
                $player->id,
                $locked_wallet->currency,
                $locked_wallet->id,
                $game_item->id,
                $billNo,
                $billNo,
                $game_item->gamePlatform->id,
            );

            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $balance,
                $locked_wallet->currency,
            );
        
            if ($locked_wallet->balance < $transAmount) 
            {
                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_BET,
                    $transAmount,
                    true,
                    self::STATUS_CODE_PLAYER_HAS_INSUFFICIENT_FUNDS,
                    $transfer_no,
                );
    
                return self::getResponse($sequence,$timestamp,self::STATUS_CODE_PLAYER_HAS_INSUFFICIENT_FUNDS,self::STATUS_DESC_PLAYER_HAS_INSUFFICIENT_FUNDS,$product_id,$username,'username');
            }

            if($transaction == self::TRANSACTION_TYPE_BET)
            {
                Log::info("==============In Bet Function ================================");

                $dup_transaction = $game_item->gameTransactionHistories()->transactionRequestNo($billNo)->status(GameTransactionHistoryConstants::STATUS_SUCCESS)->first();

                if ($dup_transaction) {
                    Log::info("==============dup_transaction error ================================");
                    return self::getResponse($sequence,$timestamp,self::STATUS_CODE_DUPLICATE_REQUEST,self::STATUS_DESC_DUPLICATE_REQUEST,$product_id,$username,'username');
                }

                $bet_round = BetRound::begin(
                    $player->id,
                    $game_item->gamePlatform->id,
                    $billNo,
                    Carbon::parse($timestamp)->setTimezone('UTC')->toDateTimeString(),
                    $locked_wallet->currency,
                    null,
                    null,
                    null,
                    null
                );

                $player_bet = Bet::place(
                    $transAmount,
                    $bet_round->round_reference,
                    $billNo,
                    $bet_round->id,
                    $game_item->id,
                    Carbon::parse($timestamp)->setTimezone('UTC')->toDateTimeString(),
                    $locked_wallet->currency,
                );

                $locked_wallet->debit($transAmount);

                $player_balance_history->gameActionSuccess(
                    $transAmount,
                    true,
                    $balance,
                    null,
                    null
                );
                
            }

            if($transaction == self::TRANSACTION_TYPE_RECKON)
            {
                if($transAmount != "0")
                {
                    $locked_wallet->credit($transAmount);

                    $player_balance_history->gameActionSuccess(
                        $transAmount,
                        false,
                        $balance,
                        null,
                        null
                    );
                }

                $player_balance_history->gameActionSuccess(
                    $transAmount,
                    true,
                    $balance,
                    null,
                    null
                );

                $game_transaction_history->gameActionSuccess(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_WIN_BET,
                    $transAmount,
                    false,
                    $balance,
                    GameTransactionHistoryConstants::NOTE_PLAYER_WON_BET,
                    null,
                    null
                );
            }
           
            if($transaction == self::TRANSACTION_TYPE_EXTBET)
            {
                if($operation == self::OPERATION_ADD)
                {
                    $locked_wallet->credit($transAmount);
                    $transactionHistoryValue = GameTransactionHistoryConstants::TRANSACTION_TYPE_WIN_BET;
                    $transactionHistoryNote = GameTransactionHistoryConstants::NOTE_PLAYER_WON_BET;
                    $isDeduction = false;
                }
                else
                {
                    $locked_wallet->debit($transAmount);
                    $transactionHistoryValue = GameTransactionHistoryConstants::TRANSACTION_TYPE_LOSE_BET;
                    $transactionHistoryNote = GameTransactionHistoryConstants::NOTE_PLAYER_LOST_BET;
                    $isDeduction = true;
                }

                $player_balance_history->gameActionSuccess(
                    $transAmount,
                    $isDeduction,
                    $balance,
                    null,
                    null
                );

                $game_transaction_history->gameActionSuccess(
                    $transactionHistoryValue,
                    $transAmount,
                    true,
                    $balance,
                    $transactionHistoryNote,
                    null,
                    null
                );
            }


            $response   =   
                [
                    "seq"           =>  $sequence,
                    "timestamp"     =>  $timestamp,
                    "code"          =>  self::STATUS_CODE_SUCCESS,
                    "message"       =>  self::STATUS_DESC_SUCCESS,
                    "data"          =>  [
                                        "trans_id"      =>  (string)$transfer_no,
                                        "product_id"    =>  $product_id,
                                        "username"      =>  $username,
                                        "currency"      =>  $currency,
                                        "balance"       =>  (string)self::RoundBalance($locked_wallet->balance)
                                        ]
                ];
            
            Log::info("==============playerTransfer provider Response ================================");
            Log::info("response Data",[$response]);
            Log::info("======================================================================");
            return response()->json($response, 200);
        } 
        catch (\Throwable $th) {
            Log::info("==============playerTransfer provider Response ================================");
            Log::info("providerError",[$th->getMessage()]);
            Log::info("======================================================================");
            return response()->json($response ??  null, 200);
        }
       
       
    }
    public static function RoundBalance($balance)
    {
        return round($balance, 5);
    }

    public static function getGameCurrency($currency): GeminiCurrencyEnums
    {
        Log::info('logging currency');
        Log::info('==========================================================');
        Log::info('Global constant currency',   [GlobalConstants::CURRENCY_PHP]);
        Log::info('Gemini enum currency',       [GeminiCurrencyEnums::PHP->value]);
        Log::info('currency parameter ',        [$currency]);
        Log::info('==========================================================');
        return match ($currency) {
            GlobalConstants::CURRENCY_PHP => GeminiCurrencyEnums::PHP,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public static function getSystemCurrency(GeminiCurrencyEnums $currency): int
    {
        return match ($currency) {
            //add BTC in global constants
            // GeminiCurrencyEnums::BTC => GlobalConstants::CURRENCY_BTC, 
            GeminiCurrencyEnums::PHP => GlobalConstants::CURRENCY_PHP,
            default => throw new Exception('Unsupported Currency')
        };
    }

    public function authenticate()
    {
         // check if token exists
         if (is_null($this->token)) {
            $result = [
                'error'     => self::STATUS_CODE_INVALID_TOKEN,
                'message'   => self::STATUS_DESC_INVALID_TOKEN,
            ];

            return json_encode($result);
        }

        // check if pid exists
        if (is_null($this->pid)) {
            $result = [
                'error'     => self::STATUS_CODE_ORDER_NOT_EXISTS,
                'message'   => self::STATUS_DESC_ORDER_NOT_EXISTS,
            ];

            return json_encode($result);
        }

        // check if secretKey exists
        if (is_null($this->secretKey)) {
            $result = [
                'error'     => self::STATUS_CODE_ORDER_NOT_EXISTS,
                'message'   => self::STATUS_DESC_ORDER_NOT_EXISTS,
            ];

            return json_encode($result);
        }

        // check if language exists
        if (is_null($this->language)) {
            $result = [
                'error'     => self::STATUS_CODE_ORDER_NOT_EXISTS,
                'message'   => self::STATUS_DESC_ORDER_NOT_EXISTS,
            ];

            return json_encode($result);
        }

        $data = [
            'pid'       =>  $this->pid,
            'token'     =>  $this->token,
            'checksum'  =>  $this->checksum,
            'language'  =>  $this->language,
            'currency'  =>  $this->currency,
        ];

        json_encode($data);

        return MD5($this->secretKey.$data);
    }
    public static function authorizeProvider($seamless_secret)
    {
        $userToken      =   PersonalAccessToken::findToken($seamless_secret);
        return ($userToken);
    }

    private static function ipNotAllowedResponse($ip)
    {
        return [
            'status' => self::STATUS_CODE_INVALID_IP,
            'msg' => self::STATUS_DESC_INVALID_IP,
            'ip' => $ip,
        ];
    }
    // seamless handling
    public static function ipNotAllowed($ip): GeminiSeamlessResponseDTO
    {
        return new GeminiSeamlessResponseDTO(self::ipNotAllowedResponse($ip), 200);
    }

    private static function authFailedResponse()
    {
        return [
            'status' => self::STATUS_CODE_INCORRECT_AUTHENTICATION,
            'msg' => self::STATUS_DESC_INCORRECT_AUTHENTICATION,
        ];
    }
    public static function authFailed(): GeminiSeamlessResponseDTO
    {
        return new GeminiSeamlessResponseDTO(self::authFailedResponse(), 200);
    }
}