<?php

namespace App\Services\Providers\PinnacleProvider\DTOs;

class PinnacleSeamlessResponseDTO
{
    public function __construct(
        public readonly array $response,
        public readonly int $status_code,
        public readonly array $extra_data = [],
        public readonly bool $is_api_hit = true
    ) {
    }
}