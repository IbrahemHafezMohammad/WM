<?php

namespace App\Services\Providers\KMProvider\DTOs;

class KMASeamlessResponseDTO
{
    public function __construct(public readonly array $response, public readonly int $status_code)
    {
    }
}