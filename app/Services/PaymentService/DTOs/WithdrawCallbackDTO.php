<?php
namespace App\Services\PaymentService\DTOs;

class WithdrawCallbackDTO
{

    public function __construct(
        public readonly int $status,
        public readonly int $response_status,
        public readonly array $response_data,
        public readonly ?string $message,
    ) {
    }
}