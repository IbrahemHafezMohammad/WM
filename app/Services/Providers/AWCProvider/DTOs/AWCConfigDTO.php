<?php

namespace App\Services\Providers\AWCProvider\DTOs;

class AWCConfigDTO
{
    public function __construct(
        public readonly string $user_id,
        public readonly ?string $total_bet_limit,
        public readonly ?string $aesext_bet_limit,
        public readonly ?string $horsebook_bet_limit,
        public readonly string $auto_bet_mode
    ) {
    }
}