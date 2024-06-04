<?php

namespace App\Services\Providers\SABAProvider\DTOs;

class SABAConfigDTO
{
    public function __construct(public string $user_name, public int $odds_type, public int $max_transfer_limit, public int $min_transfer_limit)
    {
    }
}