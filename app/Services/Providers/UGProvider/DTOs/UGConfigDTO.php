<?php

namespace App\Services\Providers\UGProvider\DTOs;

class UGConfigDTO
{
    public function __construct(
        public readonly string $odds_expression,
        public readonly string $template,
        public readonly string $theme,
        public readonly int $game_mode,
        public readonly int $favorite_sport,
        public readonly string $default_market,
    ) {
    }
}