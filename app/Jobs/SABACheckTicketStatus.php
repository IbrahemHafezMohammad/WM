<?php

namespace App\Jobs;

use App\Models\Player;
use App\Models\GameItem;
use Illuminate\Bus\Queueable;
use App\Constants\BetConstants;
use Illuminate\Support\Facades\Log;
use App\Models\PlayerBalanceHistory;
use App\Models\GameTransactionHistory;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Constants\PlayerBalanceHistoryConstants;
use App\Constants\GameTransactionHistoryConstants;
use App\Services\Providers\SABAProvider\SABAProvider;

class SABACheckTicketStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    public $timeout = 120;
    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $refId,
        public string $transfer_no,
        public GameItem $game_item,
        public Player $player
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('in the SABACheckTicketStatus');

        $transaction = $this->game_item->gameTransactionHistories()->gameTransactionNo($this->transfer_no)->status(GameTransactionHistoryConstants::STATUS_PENDING)->first();

        if ($transaction) {

            $this->player->refresh();

            $provider = new SABAProvider($this->player);

            $result = $provider->checkTicketStatus($this->refId, $this->transfer_no);

            $result = json_decode($result);

            if ($result->error_code == SABAProvider::STATUS_CODE_SUCCESS) {

                if ($result->status == SABAProvider::TRANSACTION_STATUS_SUCCESS) {

                    $player_bet = $this->game_item->bets()->reference($this->refId)->status(BetConstants::STATUS_UNSETTLED)->first();

                    $locked_wallet = $this->player->wallet()->lockForUpdate()->first();

                    $locked_wallet->debit($transaction->points);

                    $transaction->setStatus(GameTransactionHistoryConstants::STATUS_SUCCESS);

                    $transaction->playerBalanceHistory->setStatus(PlayerBalanceHistoryConstants::STATUS_SUCCESS);

                    $game_transaction_history = GameTransactionHistory::gameAction(
                        $locked_wallet->balance,
                        $this->player->id,
                        $locked_wallet->currency,
                        $locked_wallet->id,
                        $this->game_item->id,
                        $result['txId'],
                        null,
                        $this->game_item->gamePlatform->id
                    );

                    $player_balance_history = PlayerBalanceHistory::gameAction(
                        $this->player->id,
                        $locked_wallet->balance,
                        $locked_wallet->currency,
                    );

                    $transaction->points > $result['debitAmount'] ? $is_withdraw = false : $is_withdraw = true;

                    $adjustment_amount = abs($transaction->points - $result['debitAmount']);

                    $is_withdraw ? $locked_wallet->debit($adjustment_amount) : $locked_wallet->credit($adjustment_amount);

                    $player_bet->adjust(null, $result['debitAmount'], $result['debitAmount'], $result['odds']);

                    $game_transaction_history->gameActionSuccess(
                        GameTransactionHistoryConstants::TRANSACTION_TYPE_ADJUST,
                        $adjustment_amount,
                        $is_withdraw,
                        $locked_wallet->balance,
                        GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION,
                        null,
                        $player_bet->id,
                        $transaction->id,
                    );
    
                    $player_balance_history->gameActionSuccess(
                        $adjustment_amount,
                        $is_withdraw,
                        $locked_wallet->balance,
                        $game_transaction_history->id,
                        GameTransactionHistoryConstants::NOTE_ADJUST_TRANSACTION
                    );

                } elseif ($result->status == SABAProvider::TRANSACTION_STATUS_FAILED) {

                } elseif ($result->status == SABAProvider::TRANSACTION_STATUS_PENDING) {

                }
            }

            // $this->release();
        }
    }
}
