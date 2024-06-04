<?php

namespace App\Services\Providers\DS88Provider\DTOs;

class DS88SeamlessResponseDTO
{
    public function __construct(
        public readonly mixed $response,
        public readonly int $status_code,
    ) {
    }
}