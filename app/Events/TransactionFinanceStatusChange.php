<?php

namespace App\Events;

use App\Constants\GlobalConstants;
use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionFinanceStatusChange implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Transaction $transaction)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(GlobalConstants::TRANSACTIONS_BROADCAST_CHANEL_NAME),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TransactionFinanceStatusChange';
    }

    public function broadcastWith(): array
    {
        return $this->transaction->load(['actionBy:id,user_name', 'isWithdrawTransaction.riskActionBy:id,user_name', 'isDepositTransaction'])->toArray();
    }
}
