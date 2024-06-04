<?php

namespace App\Services\Providers\UGProvider\DTOs;

class UGSeamlessResponseDTO
{
    public function __construct(public readonly array $response, public readonly int $status_code)
    {
    }
}