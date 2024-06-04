<?php

namespace App\Helpers;

use App\Models\Level;
use App\Models\Player;
use Illuminate\Support\Facades\Auth;

class LevelHelper
{
    //function to update player points and check level
    //points - specify the points
    // operation - either subtract or add.  1 - Addition | 0 - Subtraction
    public static function updatePlayerPoints($points,$operation) 
    {
        $playerModel    =   new Player();
        $userId         =   Auth::user()->id;
        $playerData     =   $playerModel->select('points','player_level')->where('user_id',$userId)->first();
        // 1 - Addition | 0 - Subtraction
        if($operation == 0)
        {
            $playerData->points > 0 ? $totalPoints = $playerData->points - $points : $totalPoints = 0;
        }
        else
        {
            $totalPoints    =   $playerData->points + $points;
        }

        try 
        {
           
            $playerModel->where('user_id',$userId)->update(['points'=>$totalPoints]);

            return LevelHelper::updatePlayerLevelBasedOnPoints($userId,$totalPoints);
        } 
        catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    // update player level based on points
    public static function updatePlayerLevelBasedOnPoints($userId, $points)
    {
        try 
        {
            $updatePlayer = new Player();

            // storing in variable to call at multiple places 
            $playerQuery = $updatePlayer->select('points','player_level')->where('user_id',$userId);

            $updatePlayerLevel = $playerQuery->first();
            if($updatePlayerLevel)
            {
                $levelDetails = Level::select('level')->where('min','<=',$points)->where('max','>=',$points)->first();
                
                if($levelDetails)
                {
                    $updatePlayer->where('user_id',$userId)->update(['player_level'=>$levelDetails->level]);
                }

                // getting the updated records
                $latestPlayerData = $playerQuery->first();
                
                $data = [
                    'userCurrentLevel'=>$latestPlayerData->player_level,
                    'userCurrentPoints'=>$latestPlayerData->points,
                ];
                return response()->json([
                    'status' => 200,
                    'data' => $data,
                ], 200);
            }
        } 
        catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
        
    }
}