<?php

namespace App\Services\Providers\DagaProvider;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DagaPlayerService{

    //TODO: FIX THE FINGERPRINT
    public function getValidatedToken($username, $password){
        $hashedPassword = hash_hmac('sha1', $password, $username);

        $url = "https://api.bw9.fun/vd7prod-ecp/api/v1/login";
        $data = [
            "loginname" => $username,
            "loginpassword" => $hashedPassword,
            "fingerprint" => "3ad3cd2bbec1b4a6c3090ecb54b2269c",
            "portalid" => "EC_DESKTOP",
        ];

        $headers = [
            "Content-Type" => "application/json;charset=UTF-8",
            "Accept" => "*/*",
            "Accept-Language" => "en-US,en;q=0.9,vi;q=0.8",
            "Origin" => "https://www.bw9.fun",
            "Referer" => "https://www.bw9.fun/",
            "Sec-Ch-UA" => '"Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
            "Sec-Ch-UA-Mobile" => "?0",
            "Sec-Ch-UA-Platform" => '"Windows"',
            "Sec-Fetch-Dest" => "empty",
            "Sec-Fetch-Mode" => "cors",
            "Sec-Fetch-Site" => "same-site",
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
        ];

        $response = Http::withHeaders($headers)->post($url, $data);

        $assocArray = $response->json();
        if($assocArray && array_key_exists('token', $assocArray)){
            return [
                "status" => true,
                "token" => $assocArray['token']
            ];
        }
        if(array_key_exists('code', $assocArray) && $assocArray['code'] === 2){
            return [
                "status" => false,
                "message" => "USER_NOT_FOUND",
            ];
        }
        Log::error('Response from K36 Player Login API:'. $response->body());
        return [
            "status" => false,
            "token" => null
        ];
    }

    public function getGameLink($username, $password){
        $url = 'https://api.bw9.fun/vd7prod-ecp/api/v1/games/AWC/SV/SV-LIVE-001/launch';

        $session_token = $this->getValidatedToken($username, $password);
        if(!$session_token['status']){
            if($session_token['message'] === "USER_NOT_FOUND"){
                return [
                    "status" => false,
                    "message" => "USER_NOT_FOUND",
                ];
            }
            return [
                "status" => false,
                "message" => "NETWORK_ERROR",
            ];
            
        }

        $data = [
            'lang' => 'vi-VN',
            'platformtype' => 2,
        ];

        $headers = [
            'Accept' => '*/*',
            'Accept-Language' => 'en-US,en;q=0.9,vi;q=0.8',
            "Authorization" => $session_token['token'],
            'Content-Type' => 'application/json;charset=UTF-8',
            'Origin' => 'https://www.bw9.fun',
            'Referer' => 'https://www.bw9.fun/',
            'Sec-CH-UA' => '"Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
            'Sec-CH-UA-Mobile' => '?0',
            'Sec-CH-UA-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-site',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        ];

        $response = Http::withHeaders($headers)->put($url, $data);

        $assocArray = $response->json();
        if($assocArray && is_array($assocArray)){
            if(array_key_exists('launchurl', $assocArray)){
                return [
                    "status" => true,
                    "link" => $assocArray['launchurl']
                ];
            }
            if(array_key_exists('code', $assocArray) && $assocArray['code'] === 2){
                return [
                    "status" => false,
                    "message" => "USER_NOT_FOUND",
                ];
            }
        }
        Log::error('Response from K36 game get link API:'. $response->body());
        return [
            "status" => false,
            "message" => "NETWORK_ERROR",
        ];
    }

}