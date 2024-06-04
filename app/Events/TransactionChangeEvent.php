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

class TransactionChangeEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public function __construct( public Transaction $transaction)
    {
        //
    }

    /**
     * @return PrivateChannel[]
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(GlobalConstants::TRANSACTIONS_BROADCAST_CHANEL_NAME),
        ];
    }

    /**
     * @return string
     */

    public function broadcastAs(): string
    {
        return 'TransactionChange';
    }

    public function broadcastWith(): array
    {
        $transaction = Transaction::with([
            'player.user:id,user_name,phone',
            'paymentMethod' => function ($query) {
                $query->select('id', 'account_name', 'account_number', 'public_name', 'currency', 'payment_code');
            },
            'userPaymentMethod' => function ($query) {
                $query->select('id', 'account_name', 'account_number', 'currency');
            },
            'actionBy:id,user_name',
            'IsWithdrawTransaction',
            'IsDepositTransaction',
        ]);
        $transaction = $transaction->find($this->transaction->id);
        return $transaction->toArray();
    }
}
