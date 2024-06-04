<?php

namespace App\CronJobs;


use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use App\Services\Providers\DagaProvider\DagaProvider;

class RefreshAdminToken
{
    public function __invoke()
    {
        Log::info("in the cron job RefreshAdminToken");
        $authToken = Setting::where('key', 'GAME_AUTH_TOKEN')->first();
        if(!$authToken){
            $authToken = new Setting();
            $authToken->key = 'GAME_AUTH_TOKEN';
        }
        $dagaProvider = new DagaProvider();
        $res = $dagaProvider->getSystemAuthenticationToken();
        if($res){
            $authToken->value = $res;
            $authToken->save();
        }
    }
}