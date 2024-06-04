<?php

namespace App\Services\Providers\DagaProvider;

use App\Models\Player;
use App\Models\Setting;
use App\Services\Providers\DagaProvider\DagaAdminService;
use App\Services\Providers\DagaProvider\DagaPlayerService;
use App\Services\Providers\ProviderInterface;
use App\Services\Providers\TransferProviderInterface;
use Illuminate\Support\Facades\Log;

class DagaProvider implements ProviderInterface, TransferProviderInterface{
    protected Player $player;

    public function __construct($player = null){
        if($player){
            $this->player = $player;
        }
    }


    protected function getAuthToken(){
        $authToken = Setting::where('key', 'GAME_AUTH_TOKEN')->first();
        if(!$authToken){
            //send request to get auth token
            return null;
        }
        return $authToken->value;
    }


    public function registerToGame($language, $loginIp): ?string{
        Log::info("got into register daga func");
        $authToken = $this->getAuthToken();
        if(!$authToken){
            //send request to get auth token
            return json_encode(['status'=> "AUTH_TOKEN_MISSING"]);
        }
        $DagaAdminService = new DagaAdminService($authToken);
        $response = $DagaAdminService->checkUsername($this->player->user->user_name);
        if($response['status'] !== "SUCCESS"){
            return json_encode(['status'=> "NETWORK_ERROR"]); 
        }
        if($response['isAvailable'] !== true){
            return json_encode(['status'=> "USERNAME_NOT_AVAILABLE"]);
        }
        $response = $DagaAdminService->createUser($this->player->user->user_name, env("DAGA_HASH_KEY"));
        if($response['status'] !== "SUCCESS"){
            return json_encode(['status'=> "NETWORK_ERROR"]);
        }
        $response = $DagaAdminService->setAgentForUser($this->player->user->user_name, env('DAGA_AGENT_ID',1));
        if($response){
            return json_encode(['status'=> "SUCCESS"]);
        }
    }

    public function getBalance(): ?string
    {
        $authToken = $this->getAuthToken();
        if(!$authToken){
            //send request to get auth token
            return json_encode(['status'=> false, 'message'=> "AUTH_TOKEN_MISSING"]);
        }
        $DagaAdminService = new DagaAdminService($authToken);
        $response = $DagaAdminService->checkBalance($this->player->user->user_name);
        if($response['status'] === "SUCCESS"){
            return json_encode([ 'status' => true, 'balance' => $response['balance']]);
        }
        if($response['status'] === "USER_NOT_FOUND"){
            //register user
            //check balance again
            $res = $this->registerToGame("vn", "");
            if($res['status'] === "SUCCESS"){
                $response = $DagaAdminService->checkBalance($this->player->user->user_name);
                if($response['status'] === "SUCCESS"){
                    return json_encode([ 'status' => true, 'balance' => $response['balance']]);
                }
            }
        }
        return json_encode(['status' => false,'message' => 'NETWORK_ERROR']);
    }

    public function depositPoints($points): ?string
    {
        $authToken = $this->getAuthToken();
        if(!$authToken){
            //send request to get auth token
            return json_encode(['status'=> false, 'message'=> "AUTH_TOKEN_MISSING"]);
        }
        $DagaAdminService = new DagaAdminService($authToken);
        $response = $DagaAdminService->depositPoints($this->player->user->user_name, $points);
        if($response['status'] === "SUCCESS"){
            return json_encode([ 'status' => true, 'message' => "SUCCESS"]);
        }
        if($response['status'] === "USER_NOT_FOUND"){
            $res = $this->registerToGame("vn", "");
            if($res['status'] === "SUCCESS"){
                $response = $DagaAdminService->depositPoints($this->player->user->user_name, $points);
                if($response['status'] === "SUCCESS"){
                    return json_encode([ 'status' => true, 'message' => "SUCCESS"]);
                }
            }
        }
        return json_encode(['status' => false,'message' => 'NETWORK_ERROR']);
    }

    public function withdrawPoints($points): ?string
    {
        $authToken = $this->getAuthToken();
        if(!$authToken){
            //send request to get auth token
            return json_encode(['status'=> false, 'message'=> "AUTH_TOKEN_MISSING"]);
        }
        $DagaAdminService = new DagaAdminService($authToken);
        $response = $DagaAdminService->withdrawPoints($this->player->user->user_name, $points);
        if($response['status'] === "SUCCESS"){
            return json_encode([ 'status' => true, 'message' => "SUCCESS"]);
        }else if($response['status'] === "INSUFFICIENT_BALANCE"){
            return json_encode([ 'status' => false, 'message' => "INSUFFICIENT_BALANCE"]);
        }
        return json_encode(['status' => false,'message' => 'NETWORK_ERROR']);
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string{
        $DagaAdminService = new DagaPlayerService();
        $response = $DagaAdminService->getGameLink($this->player->user->user_name, env("DAGA_HASH_KEY"));
        if($response['status'] === true){
            return json_encode([
                'status' => "SUCCESS",
                'link' => $response['link']
            ]);
        }else if($response['message'] === "USER_NOT_FOUND"){
            return json_encode(['status' => "USER_NOT_FOUND"]);
        }
        return json_encode(['status' => "NETWORK_ERROR"]);
    }

    public function getSystemAuthenticationToken(){
        $adminService = new DagaAdminService(null);
        $res = $adminService->getValidatedToken(env("DAGA_AUTH_USERNAME"), env("DAGA_AUTH_PASSWORD"));
        if($res['status'] === true){
            return $res['token'];
        }
        return null;
    }
}