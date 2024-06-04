<?php
namespace App\Services\PaymentService\DTOs;

class DepositCallbackDTO {

    public function __construct(
        public readonly int $transactionId,
        public readonly bool $status,
        public readonly ?string $message,
        public readonly ?string $paymentSuccessTime,
        public readonly int $response_status,
        public readonly array $response_data,
    ) {
    }
    
}
