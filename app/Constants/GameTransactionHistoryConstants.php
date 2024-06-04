<?php

namespace App\Constants;

class GameTransactionHistoryConstants
{
    const TABLE_NAME = 'game_transaction_histories';

    //status
    const STATUS_SUCCESS = 1;
    const STATUS_FAILURE = 2;
    const STATUS_PENDING = 3;
    const STATUS_PROCESSING = 4;


    public static function getStatuses()
    {
        return [
            static::STATUS_SUCCESS => 'success',
            static::STATUS_FAILURE => 'failure',
            static::STATUS_PENDING => 'pending',
            static::STATUS_PROCESSING => 'processing'
        ];
    }

    public static function getStatus($statusValue)
    {
        return static::getStatuses()[$statusValue] ?? null;
    }

    //Notes

    const NOTE_PLYER_BOUGHT_IN_AND_PLACED_BET = 'Player Bought In, Player Placed a Bet';

    const NOTE_PLYER_PLACED_BET = 'Player Placed a Bet';

    const NOTE_PLYER_DEPOSIT_POINT = 'Player Deposit Point';

    const NOTE_PLYER_WITHDRAW_POINT = 'Player Withdraw Point';

    const NOTE_RESETTLE_BET = 'Resettle Bet';

    const NOTE_PLYER_PLACED_TIP = 'Player Placed a Tip';

    const NOTE_PLYER_BOUGHT_IN_AND_WON = 'Player Bought In, Player Won the Bet';

    const NOTE_PLAYER_WON_BET = 'Player Won the Bet';

    const NOTE_PLYER_BOUGHT_IN_AND_LOST = 'Player Bought In, Player Lost the Bet';

    const NOTE_PLAYER_LOST_BET = 'Player Lost the Bet';
    
    const NOTE_FREE_BET = 'Free Bet';

    const NOTE_FUND_IN_WALLET = 'Fund in the player wallet';

    const NOTE_FUND_OUT_WALLET = 'Fund out the player wallet';

    const NOTE_CANCEL_TRANSACTION = 'Cancel transaction';

    const NOTE_ROLLBACK_TRANSACTION = 'Rollback transaction';

    const NOTE_PRECANCEL_TRANSACTION = 'Pre Cancel transaction (Bet Comes After Canceling)';

    const NOTE_PRECANCEL_BET_SETTLE_TRANSACTION = 'Pre Cancel transaction (Bet and Settle Comes After Canceling)';

    const NOTE_CANCEL_TRANSACTION_PLAYER_CHEATED = 'Cancel transaction, Player Cheated';

    const NOTE_ADJUST_TRANSACTION = 'Adjustment transaction';

    const NOTE_REFUND_TRANSACTION = 'Refund transaction';

    const NOTE_REJECTION_TRANSACTION = 'Rejection transaction';

    const NOTE_CASH_OUT_TRANSACTION = 'Cash Out transaction';

    const NOTE_SETTLE_TRANSACTION = 'Settle transaction';

    const NOTE_HALF_SETTLE_TRANSACTION = 'Half Settle transaction';

    const NOTE_SETTLE_INSURANCE_TRANSACTION = 'Insurance transaction';

    const NOTE_TYPE_UNSETTLE_BET = 'Unsettle Bet';

    const NOTE_TYPE_FREE_SPIN = 'Free Spin';

    const NOTE_TYPE_PROMOTION = 'Promotion Bonus';

    const NOTE_TYPE_REWARD = 'Player Got Reward Points';

    const NOTE_TYPE_JACKPOT = 'Player Won the Jackpot';

    const NOTE_TYPE_BET_AND_SETTLE = 'Player Placed a Bet and Settled';

    const NOTE_TYPE_ACCEPT_BET = 'Player Bet Accepted';

    //transaction type
    const TRANSACTION_TYPE_CREDIT = 1;
    const TRANSACTION_TYPE_DEBIT = 2;
    const TRANSACTION_TYPE_BET = 3;
    const TRANSACTION_TYPE_TIP = 4;
    const TRANSACTION_TYPE_WIN_BET = 5;
    const TRANSACTION_TYPE_LOSE_BET = 6;
    const TRANSACTION_TYPE_ACTIVITY_LOG = 7;
    const TRANSACTION_TYPE_FREE_BET = 8;
    const TRANSACTION_TYPE_REWARD = 9;
    const TRANSACTION_TYPE_REFUND = 10;
    const TRANSACTION_TYPE_CANCEL = 11;
    const TRANSACTION_TYPE_ADJUST = 12;
    const TRANSACTION_TYPE_UNSETTLE = 13;
    const TRANSACTION_TYPE_JACKPOT = 14;
    const TRANSACTION_TYPE_BET_AND_SETTLE = 15;
    const TRANSACTION_TYPE_ACCEPT_BET = 16;
    const TRANSACTION_TYPE_REJECT_OR_ROLLBACK = 17;

    public static function getTransactionTypes()
    {
        return [
            static::TRANSACTION_TYPE_CREDIT => 'Credit',
            static::TRANSACTION_TYPE_DEBIT => 'Debit',
            static::TRANSACTION_TYPE_BET => 'Bet',
            static::TRANSACTION_TYPE_TIP => 'Tip',
            static::TRANSACTION_TYPE_WIN_BET => 'Win Bet',
            static::TRANSACTION_TYPE_ACTIVITY_LOG => 'Activity Log',
            static::TRANSACTION_TYPE_FREE_BET => 'Free Bet',
            static::TRANSACTION_TYPE_REWARD => 'Reward',
            static::TRANSACTION_TYPE_REFUND => 'Refund',
            static::TRANSACTION_TYPE_CANCEL => 'Cancel',
            static::TRANSACTION_TYPE_ADJUST => 'Adjust',
            static::TRANSACTION_TYPE_UNSETTLE => 'Unsettle',
            static::TRANSACTION_TYPE_JACKPOT => 'JACKPOT',
            static::TRANSACTION_TYPE_BET_AND_SETTLE => 'Bet and Settle',
            static::TRANSACTION_TYPE_ACCEPT_BET => 'Accept Bet',
            static::TRANSACTION_TYPE_REJECT_OR_ROLLBACK => 'Reject or Rollback'
        ];
    }

    public static function getTransactionType($transactionTypeValue)
    {
        return static::getTransactionTypes()[$transactionTypeValue] ?? null;
    }
}
