<?php

namespace App\Services\GameService;

use App\Models\Player;
use App\Constants\GamePlatformConstants;
use App\Services\Providers\KMProvider\KMProvider;
use App\Services\Providers\SSProvider\SSProvider;
use App\Services\Providers\UGProvider\UGProvider;
use App\Services\Providers\V8Provider\V8Provider;
use App\Services\Providers\AWCProvider\AWCProvider;
use App\Services\Providers\CMDProvider\CMDProvider;
use App\Services\Providers\EVOProvider\EVOProvider;
use App\Services\Providers\ONEProvider\ONEProvider;
use App\Services\Providers\VIAProvider\VIAProvider;
use App\Services\Providers\DagaProvider\DagaProvider;
use App\Services\Providers\DS88Provider\DS88Provider;
use App\Services\Providers\GeminiProvider\GeminiProvider;
use App\Services\Providers\SABAProvider\SABAProvider;
use App\Services\Providers\PinnacleProvider\PinnacleProvider;

class GlobalGameService
{
    function __construct(protected $platform, protected $game_id)
    {
    }

    public function getProvider(Player $player)
    {
        if ($this->platform === GamePlatformConstants::PLATFORM_SABA) {

            return new SABAProvider($player);

        } elseif ($this->platform === GamePlatformConstants::PLATFORM_V8) {

            return new V8Provider($player);

        } elseif ($this->platform === GamePlatformConstants::PLATFORM_KM) {

            return new KMProvider($player, $this->game_id);

        } elseif ($this->platform === GamePlatformConstants::PLATFORM_EVO) {

            return new EVOProvider($player, $this->game_id);

        } elseif ($this->platform === GamePlatformConstants::PLATFORM_UG) {

            return new UGProvider($player, $this->game_id);

        } elseif ($this->platform === GamePlatformConstants::PLATFORM_CMD) {

            return new CMDProvider($player, $this->game_id);

        } elseif ($this->platform === GamePlatformConstants::PLATFORM_DS88) {

            return new DS88Provider($player, $this->game_id);

        } elseif ($this->platform === GamePlatformConstants::PLATFORM_SS) {

            return new SSProvider($player, $this->game_id);

        } elseif (in_array($this->platform, array_keys(GamePlatformConstants::getONESubProviders()))) {

            return new ONEProvider($player, $this->game_id, $this->platform);

        } elseif ($this->platform === GamePlatformConstants::PLATFORM_PINNACLE) {

            return new PinnacleProvider($player, $this->game_id);

        } elseif ($this->platform === GamePlatformConstants::PLATFORM_VIA) {

            return new VIAProvider($player, $this->game_id);

        } 
        elseif ($this->platform === GamePlatformConstants::PLATFORM_DAGA) {

            return new DagaProvider($player, $this->game_id);

        } 
        // gemini provider
        elseif ($this->platform === GamePlatformConstants::PLATFORM_GEMINI) {

            return new GeminiProvider($player, $this->game_id);

        } 
        elseif (in_array($this->platform, array_keys(GamePlatformConstants::getAWCSubProviders()))) {

            return new AWCProvider($player, $this->platform, $this->game_id);

        }

        return null;
    }
}