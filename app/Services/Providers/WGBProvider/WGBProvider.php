<?php

namespace App\Services\Providers\WGBProvider;

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
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Log;
use App\Constants\BetRoundConstants;
use App\Models\PlayerBalanceHistory;
use Illuminate\Support\Facades\Http;
use App\Models\GameTransactionHistory;
use Illuminate\Support\Facades\Config;
use App\Constants\GamePlatformConstants;
use App\Services\Providers\ProviderInterface;
use App\Constants\GameTransactionHistoryConstants;

class WGBProvider implements ProviderInterface
{
    // languages 
    const LANG_EN = 'en-US';
    const LANG_VN = 'vi-VN';

    // layout
    const LAYOUT_PROGRESSIVE = 'progressive';
    const LAYOUT_COMPACT = 'compact';

    //reference separator
    const REFERENCE_SEPARATOR = '~~';

    //params
    protected $username;
    protected $name;
    protected $base_url;
    protected $site_id;
    protected $secret_key;
    protected $layout;
    protected $headers;
    protected $lang;
    protected $transferNo;

    function __construct(Player $player, $game_id)
    {
        $this->name = $player->user->user_name;
        $credentials = self::getCredential();
        $this->base_url = $credentials['base_url'];
        $this->site_id = $credentials['site_id'];
        $this->secret_key = $credentials['secret_key'];
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        try {

            $data = [
                'timestamp' => now()->timestamp,
                'name' => $this->name,
                'user_id' => $this->username,
                'site_id' => $this->site_id,
            ];

            
            $generated_signature = $this->generateSignature($data, $this->secret_key);

            $signature = $generated_signature['signature'];

            $extra_data = [
                'request_string_before_hashing' => $generated_signature['string_to_be_hashed'],
                'request_url' => $this->base_url . '',
            ];

            $data['sign'] = $signature;
            $data['lang'] = $this->lang;
            $data['layout'] = self::LAYOUT_PROGRESSIVE;

            $response = Http::withHeaders($this->headers)->post($this->base_url . '.gamefowlboxing.live/en/siteapi/start_seamless', $data);

            $result = $response->body();

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('WGB Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        return 'NOT_SUPPORTED';
    }

    private function generateSignature($data, $secret_key)
    {
        unset($data['signature']);

        unset($data['bets']);

        $flattenedData = $this->flattenData($data);

        ksort($flattenedData);

        $string_to_be_hashed = "";
        foreach ($flattenedData as $value) {
            if ($value !== '' && $value !== null) {
                $string_to_be_hashed .= $value;
            }
        }

        $string_to_be_hashed .= $secret_key;

        $signature = md5($string_to_be_hashed);

        return [
            'signature' => $signature,
            'string_to_be_hashed' => $string_to_be_hashed
        ];
    }

    private function flattenData($data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenData($value));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}