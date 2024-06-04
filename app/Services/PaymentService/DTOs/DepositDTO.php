<?php
namespace App\Services\PaymentService\DTOs;

class DepositDTO
{

    public function __construct(
        public readonly int $transactionId,
        public readonly int $action_status,
        public readonly ?string $message,
        public readonly ?string $payment_url,
        public readonly ?string $transfer_no,
        public readonly ?string $reference_no,
        public readonly ?string $bank_info,
        public readonly ?array $response_data,
        public readonly ?array $request_data,
        public readonly ?array $extra_data = null,
    ) {
    }
}