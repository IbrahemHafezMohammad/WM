<?php

namespace App\Services\Providers\DagaProvider;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DagaAdminService{
    protected $session_token;

    function __construct($session_token)
    {
        $this->session_token = $session_token;
    }

    public function depositPoints($user_id, $points){
        $url = "https://boapi.bw9bet.com/vd7prod-ims/api/v1/manualadjusts";

        $response = Http::withHeaders([
            "authority" => "boapi.bw9bet.com",
            "accept" => "*/*",
            "accept-language" => "en-US,en;q=0.9,vi;q=0.8",
            "authorization" => $this->session_token,
            "content-type" => "application/json;charset=UTF-8",
            "origin" => "https://bo.bw9bet.com",
            "referer" => "https://bo.bw9bet.com/",
            "sec-ch-ua" => "\"Chromium\";v=\"122\", \"Not(A:Brand)\";v=\"24\", \"Google Chrome\";v=\"122\"",
            "sec-ch-ua-mobile" => "?0",
            "sec-ch-ua-platform" => "\"Windows\"",
            "sec-fetch-dest" => "empty",
            "sec-fetch-mode" => "cors",
            "sec-fetch-site" => "same-site",
            "user-agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        ])->post($url, [
            "playerid" => $user_id,
            "walletid" => "SV",
            "manualtype" => "1",
            "reasontype" => "1",
            "adjustamt" => $points,
            "remarks" => "add points",
            "ecremarks" => "",
            "turnovertype" => "",
            "turnovervalue" => "",
            "removegwc" => "",
            "removepromoreqid" => "",
            "adminfeeratio" => "",
            "servicefee" => "",
            "subwalletautoclosevalue" => "",
            "recycletype" => "",
            "bonusttl" => "",
            "bonusexpiredate" => null,
        ]);
        $response = $response->body();

        if($response=='{"msg":"Authorization parameters not found."}'){
            return [
                'status'=> "LOGIN_FAILED"
            ];
        }

        if(strlen($response) === 38){
            return [
                'status' => "SUCCESS",
            ];
        }
        Log::error('Response from K36 Deposit API:'. $response);
        return [
            'status'=> "UNKNOWN_ERROR"
        ];
    }

    public function withdrawPoints($user_id, $points){
        $url = "https://boapi.bw9bet.com/vd7prod-ims/api/v1/manualadjusts";

        $response = Http::withHeaders([
            "authority" => "boapi.bw9bet.com",
            "accept" => "*/*",
            "accept-language" => "en-US,en;q=0.9,vi;q=0.8",
            "authorization" => $this->session_token,
            "content-type" => "application/json;charset=UTF-8",
            "origin" => "https://bo.bw9bet.com",
            "referer" => "https://bo.bw9bet.com/",
            "sec-ch-ua" => "\"Chromium\";v=\"122\", \"Not(A:Brand)\";v=\"24\", \"Google Chrome\";v=\"122\"",
            "sec-ch-ua-mobile" => "?0",
            "sec-ch-ua-platform" => "\"Windows\"",
            "sec-fetch-dest" => "empty",
            "sec-fetch-mode" => "cors",
            "sec-fetch-site" => "same-site",
            "user-agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        ])->post($url, [
            "playerid" => $user_id,
            "walletid" => "SV",
            "manualtype" => "2",
            "reasontype" => "1",
            "adjustamt" => $points,
            "remarks" => "remove points",
            "ecremarks" => "",
            "turnovertype" => "",
            "turnovervalue" => "",
            "removegwc" => "",
            "removepromoreqid" => "",
            "adminfeeratio" => "",
            "servicefee" => "",
            "subwalletautoclosevalue" => "",
            "recycletype" => "",
            "bonusttl" => "",
            "bonusexpiredate" => null,
        ]);

        $response = $response->body();
        

        if($response=='{"msg":"Authorization parameters not found."}'){
            return [
                'status'=> "LOGIN_FAILED"
            ];
        }

        if(strlen($response) === 38){
            return [
                'status' => "SUCCESS",
            ];
        }
        $res = json_decode($response, true);
        if(is_array($res) && array_key_exists("code", $res) && $res["code"] == 1 && $res["message"] == "available balance is not enough to subtract"){
            return [
                'status'=> "INSUFFICIENT_BALANCE"
            ];
        }
        Log::error('Response from K36 Withdraw API:'. $response);
        return [
            'status'=> "UNKNOWN_ERROR"
        ];
    }

    public function checkBalance($user_id){
        
        //$url = "https://boapi.bw9bet.com/vd7prod-ims/api/v1/players/list/batch/search";
        $url = "https://boapi.bw9bet.com/vd7prod-ims/api/v1/playerwallets/wallets/".$user_id."/SV";
        // $data = [
        //     "language" => 1,
        //     "status" => "1",
        //     "tags" => [],
        //     "sortcolumn" => "playerid",
        //     "sort" => "ASC",
        //     "limit" => 25,
        //     "offset" => 0,
        //     "playerid" => $user_id,
        //     "exactMatch" => true,
        //     "zonetype" => "ASIA_SHANGHAI"
        // ];
        
        $headers = [
            "Content-Type" => "application/json;charset=UTF-8",
            "Accept" => "*/*",
            "Accept-Language" => "en-US,en;q=0.9,vi;q=0.8",
            "Authorization" => $this->session_token,
            "Origin" => "https://bo.bw9bet.com",
            "Referer" => "https://bo.bw9bet.com/",
            "Sec-Ch-UA" => "\"Chromium\";v=\"122\", \"Not(A:Brand\";v=\"24\", \"Google Chrome\";v=\"122\"",
            "Sec-Ch-UA-Mobile" => "?0",
            "Sec-Ch-UA-Platform" => "\"Windows\"",
            "Sec-Fetch-Dest" => "empty",
            "Sec-Fetch-Mode" => "cors",
            "Sec-Fetch-Site" => "same-site",
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        ];
        
        $response = Http::withHeaders($headers)->get($url);
        
        $res = $response->json();
        if(isset($res['balance'])) {
            return [
                "status" => "SUCCESS",
                "balance" => $res['balance']
            ];
            // if(is_array($res['data'])){

            //     if(count($res['data']) === 0){
            //         return [
            //             "status" => "USER_NOT_FOUND"
            //         ];
            //     }
            // }
        }else if(isset($res['msg']) && $res['msg'] == "invalid playerid"){
            return [
                'status'=> "USER_NOT_FOUND"
            ];
        }else {
            Log::error('Response from K36 Check balance API:'. $response->body());
            return [
                'status'=> "LOGIN_FAILED"
            ];
        }
        Log::error('Response from K36 Check balance API:'. $response->body());
        return [
            'status'=> "UNKNOWN_ERROR"
        ];
    }

    public function createUser($userID, $password){
        $url = "https://boapi.bw9bet.com/vd7prod-ims/api/v1/players";

        $hashedPassword = hash_hmac('sha1', $password, $userID);

        $data = [
            "playerid" => $userID,
            "firstname" => "Test player name",
            "password" => $hashedPassword,
            "currency" => "VND2",
            "mobile" => "",
            "email" => "",
            "country" => "",
            "city" => "",
            "birthday" => "",
            "internalplayer" => false,
            "im1" => "",
            "im2" => "",
            "vipid" => "5d7fce42-aafc-11e6-80f5-76304dec7eb7",
            "language" => 4
        ];

        $response = Http::withHeaders([
            "Content-Type" => "application/json;charset=UTF-8",
            "Accept" => "*/*",
            "Accept-Language" => "en-US,en;q=0.9,vi;q=0.8",
            "Authorization" => $this->session_token,
            "Origin" => "https://bo.bw9bet.com",
            "Referer" => "https://bo.bw9bet.com/",
            "Sec-Ch-UA" => "\"Chromium\";v=\"122\", \"Not(A:Brand\";v=\"24\", \"Google Chrome\";v=\"122\"",
            "Sec-Ch-UA-Mobile" => "?0",
            "Sec-Ch-UA-Platform" => "\"Windows\"",
            "Sec-Fetch-Dest" => "empty",
            "Sec-Fetch-Mode" => "cors",
            "Sec-Fetch-Site" => "same-site",
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        ])->post($url, $data);

        $assocArray = $response->json();
        
        if($assocArray && is_array($assocArray)){
            if(array_key_exists('playerid', $assocArray)){
                return [
                    'status' => "SUCCESS",
                ];
            }else if(array_key_exists('code', $assocArray) && ($assocArray['code'] == 1 || $assocArray['code'] == 10)){
                return [
                    'status'=> "USERID_INVALID"
                ];
            }
        }

        Log::error('Response from K36 Create User API:'. $response->body());
        return [
            'status'=> "NETWORK_ERROR"
        ];
    }

    public function checkUsername($username){
        $url = "https://boapi.bw9bet.com/vd7prod-ims/api/v1/players/list/lookup?q=".$username;
        $response = Http::withHeaders([
            "Accept" => "*/*",
            "Accept-Language" => "en-US,en;q=0.9,vi;q=0.8",
            "Authorization" => $this->session_token,
            "Origin" => "https://bo.bw9bet.com",
            "Referer" => "https://bo.bw9bet.com/",
            "Sec-Ch-UA" => "\"Chromium\";v=\"122\", \"Not(A:Brand\";v=\"24\", \"Google Chrome\";v=\"122\"",
            "Sec-Ch-UA-Mobile" => "?0",
            "Sec-Ch-UA-Platform" => "\"Windows\"",
            "Sec-Fetch-Dest" => "empty",
            "Sec-Fetch-Mode" => "cors",
            "Sec-Fetch-Site" => "same-site",
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        ])->get($url);
        
        $isAvailable = null;
        
        if ($response->successful()) {
            $res = $response->json();
            if (is_array($res)) {
                $isAvailable = count($res) == 0;
            }
            return 
            [
                "status" => "SUCCESS",
                "isAvailable" => $isAvailable
            ];
        }
        if($isAvailable === null){
            Log::error('Response from K36 Check Username API:'. $response->json());
        }
        return [
            'status'=> "UNKNOWN_ERROR"
        ];
    }

    public function getValidatedToken($username, $password){
        $hashedPassword = hash_hmac('sha1', $password, $username);
        $url = "https://boapi.bw9bet.com/vd7prod-ims/api/v1/login";
        $data = [
            "userid" => $username,
            "password" => $hashedPassword
        ];

        $response = Http::withHeaders([
            "Content-Type" => "application/json;charset=UTF-8",
            "Accept" => "*/*",
            "Accept-Language" => "en-US,en;q=0.9,vi;q=0.8",
            "Authorization" => "undefined",
            "Origin" => "https://bo.bw9bet.com",
            "Referer" => "https://bo.bw9bet.com/",
            "Sec-Ch-UA" => "\"Chromium\";v=\"122\", \"Not(A:Brand\";v=\"24\", \"Google Chrome\";v=\"122\"",
            "Sec-Ch-UA-Mobile" => "?0",
            "Sec-Ch-UA-Platform" => "\"Windows\"",
            "Sec-Fetch-Dest" => "empty",
            "Sec-Fetch-Mode" => "cors",
            "Sec-Fetch-Site" => "same-site",
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        ])->post($url, $data);

        $assocArray = $response->json();
        if(isset($assocArray['token'])) {
            return [
                "status" => true,
                "token" => $assocArray['token']
            ];
        }
        Log::error('Response from K36 Get Token API:'. $response->body());
        return [
            "status" => false,
            "token" => null
        ];
    }

    public function setAgentForUser($playerId, $agentId){
        $url = "https://boapi.bw9bet.com/vd7prod-ims/api/v1/ulagent/changeLine/playersByAuth";
        $data = [
            [
                "downLineAccount" => $playerId,
                "upLineAgentId" => $agentId
            ]
        ];
        $response = Http::withHeaders([
            "Accept" => "*/*",
            "Accept-Language" => "en-US,en;q=0.9,vi;q=0.8",
            "Authorization" => $this->session_token,
            "Content-Type" => "application/json;charset=UTF-8",
            "Origin" => "https://bo.bw9bet.com",
            "Referer" => "https://bo.bw9bet.com/",
            "Sec-Ch-UA" => "\"Chromium\";v=\"122\", \"Not(A:Brand\";v=\"24\", \"Google Chrome\";v=\"122\"",
            "Sec-Ch-UA-Mobile" => "?0",
            "Sec-Ch-UA-Platform" => "\"Windows\"",
            "Sec-Fetch-Dest" => "empty",
            "Sec-Fetch-Mode" => "cors",
            "Sec-Fetch-Site" => "same-site",
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        ])->put($url, $data);

        $http_status = $response->status();

        if ($http_status == 204) {
            return true;
        }
        return null;
    }

    public function resetPassword($username, $newPassword){
        $hashedPassword = hash_hmac('sha1', $newPassword, $username);

        $url = "https://boapi.bw9bet.com/vd7prod-ims/api/v1/players/" .$username. "/password";
        $data = [
            "newpassword" => $hashedPassword
        ];
        $response = Http::withHeaders([
            "authority" => "boapi.bw9bet.com",
            "accept" => "*/*",
            "accept-language" => "en-US,en;q=0.9,vi;q=0.8",
            "authorization" => $this->session_token,
            "content-type" => "application/json;charset=UTF-8",
            "origin" => "https://bo.bw9bet.com",
            "referer" => "https://bo.bw9bet.com/",
            "sec-ch-ua" => "\"Chromium\";v=\"122\", \"Not(A:Brand\";v=\"24\", \"Google Chrome\";v=\"122\"",
            "sec-ch-ua-mobile" => "?0",
            "sec-ch-ua-platform" => "\"Windows\"",
            "sec-fetch-dest" => "empty",
            "sec-fetch-mode" => "cors",
            "sec-fetch-site" => "same-site",
            "user-agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        ])->put($url, $data);

        $http_status = $response->status();

        if ($http_status == 204) {
            return true;
        }
        return null;
    }


    


}