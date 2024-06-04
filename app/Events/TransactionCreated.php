<?php

namespace App\Events;

use App\Constants\GlobalConstants;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(private Transaction $transaction)
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
        return 'TransactionCreated';
    }

    public function broadcastWith(): array
    {
        $transaction = $this->transaction->load([
            'player.user:id,user_name,phone,profile_pic',
            'paymentMethod' => function ($query) {
                $query->select('id', 'account_name', 'account_number', 'public_name', 'currency', 'payment_code');
            },
            'userPaymentMethod' => function ($query) {
                $query->select('id', 'account_name', 'account_number', 'currency');
            },
            'actionBy:id,user_name',
        ]);
        ((bool) $transaction->isWithdraw) ? $transaction->load(['IsWithdrawTransaction.riskActionBy']) : $transaction->load('IsDepositTransaction');
        return $transaction->toArray();
    }
}
