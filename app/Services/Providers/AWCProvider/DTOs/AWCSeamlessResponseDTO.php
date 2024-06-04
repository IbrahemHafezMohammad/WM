<?php

namespace App\Services\Providers\AWCProvider\DTOs;

class AWCSeamlessResponseDTO
{
    public function __construct(
        public readonly array $response,
        public readonly int $status_code
    ) {
    }
}