<?php

namespace App\Services\Providers\VIAProvider\DTOs;

class VIASeamlessResponseDTO
{
    public function __construct(public readonly array $response, public readonly int $status_code)
    {
    }
}