<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ApiHit;
use App\Models\Player;
use App\Services\Providers\GeminiProvider\GeminiProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class GeminiController extends Controller
{
    public function getPlayerInfo(Request $request)
    {

        try
        {
            Log::info('=====================Gemini Controller Logs==========================');
            Log::info('Request:-',[$request]);
            $userToken      =   PersonalAccessToken::findToken($request->token);
            Log::info('$userToken:-',[$userToken]);

            $getUserId = $userToken->tokenable_id;
            Log::info('$userDetails:-',[$getUserId]);
            
            $playerModel = Player::find($getUserId);
            Log::info('$playerModel:-',[$playerModel]);
            $geminiProvider = new GeminiProvider($playerModel,null);
            // $geminiProvider = app(GeminiProvider::class);
            
            // $gameType = 'CaribbeanBingo'; // Set your game type here
            
            // $geminiProvider = app(GeminiProvider::class, ['gameType' => $gameType]);
            
            Log::info('=====================Gemini Controller Logs End======================');
            

            $response = $geminiProvider->getPlayerInfo(
                $request->seq,
                $request->timestamp,
                $request->product_id, 
                $request->token
            );

            ApiHit::createApiHitEntry(
                $request,
                $response,
                null,
                null,
                $game_platform ?? null,
            );

            return $response;
        }
        catch (\Exception $e)
        {
            Log::info('=====================Gemini Controller Exception Logs==========================');
            Log::info('Request:-',[$request]);
            Log::info('Exception:-',[$e->getMessage()]);
            Log::info('=====================Gemini Controller Logs Exception End======================');
            return $e->getMessage();
        }
    }

    public function checkBalance(Request $request)
    {
        try
        {
            Log::info('=====================Gemini checkBalance Controller Logs==========================');
            Log::info('Request:-',[$request]);
            $userToken      =   PersonalAccessToken::findToken($request->token);
            Log::info('$userToken:-',[$userToken]);
            $getUserId = $userToken->tokenable_id;
            $playerModel = Player::find($getUserId);
            Log::info('$playerModel:-',[$playerModel]);
            $geminiProvider = new GeminiProvider($playerModel,null);

            $response = $geminiProvider->checkBalance(
                $request->seq,
                $request->timestamp,
                $request->product_id, 
                $request->token,
                $request->username
            );

            ApiHit::createApiHitEntry(
                $request,
                $response,
                null,
                null,
                $game_platform ?? null,
            );

            return $response;
        }
        catch (\Exception $e)
        {
            Log::info('=====================Gemini checkBalance Controller Exception Logs==========================');
            Log::info('Request:-',[$request]);
            Log::info('Exception:-',[$e->getMessage()]);
            Log::info('=====================Gemini checkBalance Controller Logs Exception End======================');
            return $e->getMessage();
        }
    }

    public function playerTransfer(Request $request)
    {
        try 
        {
            Log::info('=====================Gemini playerTranfer Controller Logs==========================');
            $userToken      =   PersonalAccessToken::findToken($request->token);
            Log::info('$userToken:-',[$userToken]);
            $getUserId = $userToken->tokenable_id;
            Log::info('Request:-',[$request]);
            $playerModel = Player::find($getUserId);
            Log::info('$playerModel:-',[$playerModel]);
            $geminiProvider = new GeminiProvider($playerModel,null);
    
            // playerTransfer($username,$gameType,$gameCode = null,$billNo,$transAmount = null,$transaction,$currency = null)
            $response = $geminiProvider->playerTransfer(
                $request->seq,
                $request->timestamp,
                $request->product_id, 
                $request->token,
                $request->username,
                $request->data['gametype'],
                $request->data['gamecode'],
                $request->data['billno'],
                $request->data['transamount'],
                $request->data['transaction'],
                $request->data['currency'],
            );
    
            ApiHit::createApiHitEntry(
                $request,
                $response,
                null,
                null,
                $game_platform ?? null,
            );
            Log::info('=====================Gemini playerTranfer Controller Logs End======================');
    
            return $response;
    
        } 
        catch (\Throwable $th) 
        {
            Log::info('=====================Gemini Controller Exception Logs==========================');
            Log::info('Request:-',[$request]);
            Log::info('Exception:-',[$th->getMessage()]);
            Log::info('=====================Gemini Controller Logs Exception End======================');
            return $th->getMessage();
        }
       
    }
    // public function getBingoGameList()
    // {
    //     $url = 'https://uat-game-api.elsgame.cc/api/v1/operator/game/list';

    //     $secretKey = '1dfa5341';
    //     $request_body = [
    //         "seq" => "1","product_id"=> "GMM3717"
    //     ];
    //     $hashedKey = md5($secretKey.json_encode($request_body));
    //     $headers = [
    //         'Content-Type' => 'application/json',
    //         'els-access-key' => $hashedKey,
    //     ];

    //     $response = Http::withHeaders($headers)->post($url,$request_body);

    //     return $response->body();
    // }
}
