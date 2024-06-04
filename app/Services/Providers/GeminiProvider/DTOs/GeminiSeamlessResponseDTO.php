<?php

    namespace App\Services\Providers\GeminiProvider\DTOs;
    class GeminiSeamlessResponseDTO
    {
        public function __construct(public readonly array $response, public readonly int $status_code)
        {
        }
    }