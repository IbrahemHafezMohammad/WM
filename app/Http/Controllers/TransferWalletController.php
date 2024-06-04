<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GameItem;
use App\Services\GameService\TransferGameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\GameTransactionHistory;
use App\Models\PlayerBalanceHistory;
use App\Constants\GameTransactionHistoryConstants;

class TransferWalletController extends Controller
{
    public function getGameBalance(GameItem $game_item)
    {
        $player = auth()->user()->player;
        $response = $this->getTransferGameWalletBalance($game_item, $player);
        $res = json_decode($response, true);
        if($res && is_array($res)){
            if($res['status'] === true){
                return [
                    'balance' => $res['balance'],
                    'message' => 'SUCCESS',
                    'status' => true
                ];
            }else{
                return response()->json($response, 401);
            }
        }
        return response()->json(['message' => 'NETWORK_ERROR', 'status'=> false], 401);
    }

    public function getAllGameBalances(){
        $player = auth()->user()->player;

        $locked_wallet = $player->wallet()->lockForUpdate()->first();

        $balances = [['id'=> null, "name" => "", "balance"=> $locked_wallet->balance]];

        $daga_game = GameItem::whereRelation('gamePlatform', 'platform_code', "DAGA")->first();
        if($daga_game){
            $res = $this->getTransferGameWalletBalance($daga_game, $player);
            $res = json_decode($res, true);
            if($res && is_array($res) && $res['status'] === true){
                array_push($balances, ['id'=> $daga_game->id, "name" => $daga_game->name, "balance"=> $res['balance']]);
            }else{
                array_push($balances, ['id'=> $daga_game->id, "name" => $daga_game->name, "balance"=> null]);
            }
        }
        return $balances;
    }


    protected function getTransferGameWalletBalance(GameItem $game_item, $player){
        $platform_code = $game_item->gamePlatform->platform_code;
        if(!$platform_code){
            return json_encode(['message' => 'INVALID_GAME_ID', 'status'=> false]);
        }
        $game_service = new TransferGameService($platform_code);
        $provider = $game_service->getProvider($player);
        if(!$provider){
            return json_encode(['message' => 'INVALID_GAME_ID', 'status'=> false]);
        }
        $res = $provider->getBalance();
        return $res;
    }


    public function depositPoints(Request $request, GameItem $game_item){
        $validated = $request->validate([
            'points' => 'required|numeric|min:1'
        ]);
        $player = auth()->user()->player;
        //GET PLATFORM
        $platform_code = $game_item->gamePlatform->platform_code;
        if(!$platform_code){
            return response()->json(['message' => 'INVALID_GAME_ID', 'status'=> false], 400);
        }
        $game_service = new TransferGameService($platform_code);
        $provider = $game_service->getProvider($player);
        if(!$provider){
            return response()->json(['message' => 'INVALID_GAME_ID', 'status'=> false], 400);
        }
        //check and deduct from account
        DB::beginTransaction();
        $locked_wallet = $player->wallet()->lockForUpdate()->first();
        if($locked_wallet->balance < $validated['points']){
            return response()->json(['message' => 'INSUFFICIENT_BALANCE', 'status'=> false], 200);
        }
        $locked_wallet->debit($validated['points']);
        $gameTransactionHistory = GameTransactionHistory::pointSuccess($locked_wallet , $player , $validated['points'] , $locked_wallet->balance - $validated['points'] , $game_item->id , $game_item->gamePlatform->id , false ,GameTransactionHistoryConstants::NOTE_PLYER_DEPOSIT_POINT);
        PlayerBalanceHistory::pointSuccess($locked_wallet ,  $validated['points'] , $locked_wallet->balance - $validated['points'] , false , GameTransactionHistoryConstants::NOTE_PLYER_DEPOSIT_POINT , $gameTransactionHistory->id);
        $res = $provider->depositPoints($validated['points']);
        $res = json_decode($res, true);
        if($res && $res['status'] === true){
            DB::commit();
            return [
                'message' => 'SUCCESS',
                'status' => true
            ];
        }
        DB::rollBack();
        return response()->json(['message' => 'NETWORK_ERROR', 'status'=> false], 401);
    }

    public function withdrawPoints(Request $request, GameItem $game_item){
        $validated = $request->validate([
            'points' => 'required|numeric|min:1'
        ]);
        $player = auth()->user()->player;
        $platform_code = $game_item->gamePlatform->platform_code;
        if(!$platform_code){
            return response()->json(['message' => 'INVALID_GAME_ID', 'status'=> false], 400);
        }
        $game_service = new TransferGameService($platform_code);
        $provider = $game_service->getProvider($player);
        if(!$provider){
            return response()->json(['message' => 'INVALID_GAME_ID', 'status'=> false], 400);
        }
        $res = $provider->withdrawPoints($validated['points']);
        $res = json_decode($res, true);
        if($res && is_array($res)){
            if($res['status'] === true){
                try{
                    $locked_wallet = $player->wallet()->lockForUpdate()->first();
                    $locked_wallet->credit($validated['points']);
                    $gameTransactionHistory = GameTransactionHistory::pointSuccess($locked_wallet , $player , $validated['points'] , $locked_wallet->balance + $validated['points'] , $game_item->id , $game_item->gamePlatform->id , true , GameTransactionHistoryConstants::NOTE_PLYER_WITHDRAW_POINT);
                    PlayerBalanceHistory::pointSuccess($locked_wallet , $validated['points'] , $locked_wallet->balance + $validated['points'] , true , GameTransactionHistoryConstants::NOTE_PLYER_WITHDRAW_POINT , $gameTransactionHistory->id);
                    return [
                        'message' => 'SUCCESS',
                        'status' => true
                    ];
                }catch(\Exception $e){
                    $res = $provider->depositPoints($validated['points']);
                    $res = json_decode($res, true);
                    if(!$res || $res['status'] !== true){
                        Log::error("EXCEPTION CATCHED WHEN WITHDRAW BUT FAILED TO UNDO POINTS FOR PLAYER. ");
                    }
                    return response()->json(['message' => 'NETWORK_ERROR', 'status'=> false], 401);
                }
            }else if($res['message'] === "INSUFFICIENT_BALANCE"){
                return response()->json(['message' => 'INSUFFICIENT_BALANCE', 'status'=> false], 200);
            }
        }
        return response()->json(['message' => 'NETWORK_ERROR', 'status'=> false], 401);
    }
}
