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
use App\Constants\GamePlatformConstants;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Constants\GameTransactionHistoryConstants;
use App\Services\Providers\CMDProvider\CMDProvider;

class CMDCheckTicketStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 48;

    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ref_no,
        public int $game_item_id,
        public int $player_id
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('in the CMDCheckTicketStatus');

        $game_item = GameItem::findOrFail($this->game_item_id);

        $player = Player::findOrFail($this->player_id);

        $player_bet = $game_item->bets()->reference($this->ref_no)->first();

        $attempts = $this->attempts();

        if ($player_bet->status == BetConstants::STATUS_UNSETTLED) {

            $provider = new CMDProvider($player, GamePlatformConstants::CMD_GAME_CODE_LOBBY);

            $result = $provider->queryBetRecord($this->ref_no);

            $wallet = $player->wallet;

            // init the game transaction history
            $game_transaction_history = GameTransactionHistory::gameAction(
                $wallet->balance,
                $player->id,
                $wallet->currency,
                $wallet->id,
                $game_item->id,
                null,
                null,
                $game_item->gamePlatform->id
            );

            // init the balance history
            $player_balance_history = PlayerBalanceHistory::gameAction(
                $player->id,
                $wallet->balance,
                $wallet->currency,
            );

            $result = json_decode($result);

            if ($result->Code == '0') {

                $bet_record = $result->Data[0];

                if ($bet_record->ReferenceNo != $this->ref_no) {
                    // save the error in the game transaction history (reference_no doesn't match)

                    $game_transaction_history->gameActionFailed(
                        GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                        0,
                        false,
                        'CMD Job Reference No does not match',
                    );

                    return;
                }

                // if bet is still running dispatch after 1 hour
                if ($bet_record->WinLoseStatus === 'P') { // pending 

                    if ($attempts > 24) {

                        $game_transaction_history->gameActionFailed(
                            GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                            0,
                            false,
                            'CMD Job Bet is still running after 24 hours',
                        );
                    }
    
                    $this->release(1 * 60 * 60); //1 * 60 * 60
                    return;
                }

                // if not then check bet status and it should lost otherwise there's an error (save in the game transaction history)
                // to check if it's lost or not the WinAmount should be 0
                if ($bet_record->WinLoseStatus !== 'LA') { // lose all

                    $game_transaction_history->gameActionFailed(
                        GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                        0,
                        false,
                        'CMD Job Bet is not lost',
                    );

                    return;
                }

                // save the records 
                $amount = 0;

                $timestamp = CMDProvider::convertTicksToUtcString($bet_record->StateUpdateTs);

                $winloss = $amount - $player_bet->bet_amount;

                $bet_round = $player_bet->betRound;

                $player_bet->settle($amount, $timestamp);
                
                $bet_round->close($timestamp, $winloss, null, null, $amount);

                $refer_transaction = $player_bet->latestSuccessfulGameTransactionHistory;

                $game_transaction_history->gameActionSuccess(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_CREDIT,
                    $amount,
                    false,
                    $wallet->balance,
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_LOSE_BET,
                    null,
                    $player_bet->id,
                    $refer_transaction?->id,
                );

                $player_balance_history->gameActionSuccess(
                    $amount,
                    false,
                    $wallet->balance,
                    $game_transaction_history->id,
                    GameTransactionHistoryConstants::NOTE_HALF_SETTLE_TRANSACTION
                );

                return;
            } else {

                $game_transaction_history->gameActionFailed(
                    GameTransactionHistoryConstants::TRANSACTION_TYPE_ACTIVITY_LOG,
                    0,
                    false,
                    'CMD Job Error in response , Code : ' . $result->Code . ', Message : ' . $result->Message,
                );
    
                $this->release(1 * 60 * 60); //1 * 60 * 60
                
                return;
                // if there's an error in the response save it in the game transaction history and check the logs 
            }

        } else {
            Log::info('Bet is already settled');
            return;
        }
    }
}
