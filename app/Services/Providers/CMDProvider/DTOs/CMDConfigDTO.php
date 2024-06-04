<?php

namespace App\Services\Providers\CMDProvider\DTOs;

class CMDConfigDTO
{
    public function __construct(public string $user_id, public string $group_id)
    {
    }
}