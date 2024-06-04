<?php

namespace App\Services\Providers\EVOProvider\DTOs;

class EVOSeamlessResponseDTO
{
    public function __construct(
        public readonly array $response,
        public readonly int $status_code
    ) {
    }
}