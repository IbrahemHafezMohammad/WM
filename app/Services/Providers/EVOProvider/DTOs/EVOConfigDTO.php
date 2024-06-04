<?php

namespace App\Services\Providers\EVOProvider\DTOs;

class EVOConfigDTO
{
    public function __construct(public string $user_id, public string $group_id)
    {
    }
}