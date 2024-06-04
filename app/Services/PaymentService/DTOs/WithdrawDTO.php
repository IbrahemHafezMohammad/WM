<?php
namespace App\Services\PaymentService\DTOs;

use League\CommonMark\Reference\Reference;

class WithdrawDTO
{

    public function __construct(
        public readonly ?string $reference,
        public readonly int $action_status,
        public readonly ?string $message,
    ) {
    }
}