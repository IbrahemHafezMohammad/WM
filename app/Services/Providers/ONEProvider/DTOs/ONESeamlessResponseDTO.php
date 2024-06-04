<?php

namespace App\Services\Providers\ONEProvider\DTOs;

use PhpParser\Node\Expr\BooleanNot;

class ONESeamlessResponseDTO
{
    public function __construct(
        public readonly array $response,
        public readonly int $status_code,
        public readonly array $extra_data = [],
    ) {
    }
}
