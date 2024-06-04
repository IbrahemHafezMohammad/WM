<?php

namespace App\Services\Providers\SABAProvider\DTOs;

class SABASeamlessResponseDTO
{
    public function __construct(public readonly array $response, public readonly int $status_code)
    {
    }
}