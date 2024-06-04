<?php

namespace App\Events;

use App\Constants\GlobalConstants;
use App\Models\Deposit;
use App\Models\Withdraw;
use App\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionWithdrawLockChange implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public function __construct( public Withdraw $isWithdraw)
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
        return 'TransactionWithdrawLockChange';
    }

    public function broadcastWith(): array
    {
          return ['is_withdraw_transaction'=>$this->isWithdraw];
    }
}

