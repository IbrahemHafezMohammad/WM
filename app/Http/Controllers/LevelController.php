<?php

namespace App\Http\Controllers;

use App\Helpers\LevelHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level;
use App\Models\Player;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LevelController extends Controller
{
    // Display all levels
    // ==========================================
    public function viewAllLevels()
    {
        // add this line wherever you want to add the points
        // return LevelHelper::updatePlayerPoints(3,1);
        $data = Level::orderBy('level')->get();

        return response()->json([
            'status' => 200,
            'message' => "SUCCESS",
            'data' => $data,
        ], 200);
    }
    public function createLevel(Request $request)
    {
        // creating a level
        try
        {
            $level = new Level();
            $level->level_name  = $request->level_name;
            $level->level       = $request->level;
            $level->min         = $request->min;
            $level->max         = $request->max;
            $level->save();

            return response()->json([
                'status' => 200,
                'message' => "LEVEL_CREATED_SUCCESSFULLY",
            ], 200);
        }
        catch (\Throwable $e)
        {
            Log::info('---------------------------------------------------------------------------------------------');
            Log::info($e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
            Log::info('---------------------------------------------------------------------------------------------');

        }
    }   
    public function updateLevel(Request $request,$id)
    {
        // updating a level
        try
        {
            $level = Level::find($id);
            $level->level_name = $request->level_name;
            $level->level      = $request->level;
            $level->min        = $request->min;
            $level->max        = $request->max;
            $level->save();

            return response()->json([
                'status' => 200,
                'message' => "LEVEL_UPDATED_SUCCESSFULLY",
            ], 200);
        }
        catch (\Throwable $e)
        {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function deleteLevel(string $id)
    {
        //deleting a level
        try 
        {
            $level = Level::find($id);
            $level->delete();

            return response()->json([
                'status' => 200,
                'message' => "LEVEL_DELETED_SUCCESSFULLY",
            ], 200);
        } 
        catch (\Throwable $e)
        {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==========================================

    
    //get level of loggedIn User 
    public function getUserLevel()
    {
        $id =   Auth::user()->id;
                return DB::table('players')->select('points','player_level')->where(['user_id' => $id])->first();

    }

    public function checkLevelBasedOnPoints()
    {

        $userId     = Auth::user()->id;
        if(empty(Auth::user()->id))
        {
            // $userId     = 16;
            return response()->json([
                'status' => 500,
                'message' => "User Id not found",
            ], 500);
        }

        try 
        {
            // calling the function to get player details
            $userData               = $this->getUserLevel($userId);
            $points                 = $userData->points;
            $userCurrentLevel       = $userData->player_level;

            // creating level model instance
            $levelModel      = new Level();
            
            // fetching the level data
            $levelData      =  $levelModel->where('min', '<=', $points)
                                            ->where('max', '>=', $points)
                                            ->first();

            $updatePlayerLevel = Player::select('points','player_level')->where('user_id',$userId)->first();

            $getCurrentUserLevelDetails = $levelModel->where('level',$updatePlayerLevel->player_level)->first();
            if(!empty($levelData))
            {
                // Update level if user current points are greater than the current level max points 
                if($levelData->level > $userCurrentLevel)
                {
                    $updatePlayerLevel->player_level = $levelData->level;
                    $updatePlayerLevel->save();
                    $levelData->userCurrentLevel     = $updatePlayerLevel->player_level;
                }
    
                $levelData->userCurrentLevel     = $userData->player_level;
                $levelData->userCurrentPoints    = $points;

                return response()->json([
                    'status' => 200,
                    'message' => "SUCCESS",
                    'data' => $levelData,
                ], 200);
            }

            //if the user exceeds the max points
            $levelData = [
                'userCurrentLevel'=>$getCurrentUserLevelDetails->level,
                'userCurrentPoints'=>$updatePlayerLevel->points,
                'max' => $getCurrentUserLevelDetails->max,
                'level_name'=>$getCurrentUserLevelDetails->level_name,
                'level'=>$getCurrentUserLevelDetails->level
            ];
            
            return response()->json([
                'status' => 200,
                'message' => "SUCCESS",
                'data' => $levelData,
            ], 200);
            
        } 
        catch (\Throwable $th) 
        {
            return response()->json([
                'status' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
        
    }
}
