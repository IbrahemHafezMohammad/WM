<?php

namespace App\Services\Providers\KMProvider\DTOs;

class KMConfigDTO
{
    public function __construct(public string $user_id, public int $bet_limit)
    {
    }
}