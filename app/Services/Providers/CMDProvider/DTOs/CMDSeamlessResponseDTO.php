<?php

namespace App\Services\Providers\CMDProvider\DTOs;

use App\Services\Providers\CMDProvider\CMDProvider;

class CMDSeamlessResponseDTO
{
    public function __construct(
        public readonly mixed $response,
        public readonly int $status_code,
        public readonly array $extra_data = [],
        public readonly string $response_type = CMDProvider::RESPONSE_TYPE_JSON,
    ) {
    }
}