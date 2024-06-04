<?php

namespace App\Services\GameService;

use App\Constants\GamePlatformConstants;
use App\Models\Player;
use App\Services\Providers\DagaProvider\DagaProvider;

class TransferGameService
{
    function __construct(protected $platform)
    {

    }

    public function getProvider(Player $player)
    {
        if ($this->platform === GamePlatformConstants::PLATFORM_DAGA) {
            return new DagaProvider($player);
        }
        return null;
    }
}