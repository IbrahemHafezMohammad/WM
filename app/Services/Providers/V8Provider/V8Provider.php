<?php

namespace App\Services\Providers\V8Provider;

use App\Constants\GamePlatformConstants;
use App\Helpers\NumberHelper;
use App\Models\Player;
use App\Services\Providers\ProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\Providers\V8Provider\Encryption\AesEcb;

class V8Provider implements ProviderInterface
{
    protected $username;
    protected $password;
    protected $id;
    protected $agent;
    protected $base_url;
    protected $headers;
    protected $transferNo;
    protected $md5_key;
    protected $aes_key;
    protected $aes_algorithm;
    protected $order_id;
    protected $transaction_no;

    function __construct(Player $player)
    {
        $this->username = $player->user->user_name . '_test'; // "_test" is optional
        $this->password = $this->username . GamePlatformConstants::V8_GAME_TYPE_V8;
        $this->id = $player->id;
        $this->agent = Config::get('app.v8_agent');
        $this->base_url = Config::get('app.v8_base_url');
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $this->md5_key = Config::get('app.v8_md5_key');
        $this->aes_key = Config::get('app.v8_aes_key');
        $this->aes_algorithm = new AesEcb();
    }

    public function loginToGame($language, $loginIp, $deviceType): ?string
    {
        try {

            $this->order_id = $this->aes_algorithm->getOrderId($this->agent) . $this->username;

            $line_code = $this->id . substr($this->username, 0, 10 - strlen((string) $this->id));

            $params = 's=0' . '&account=' . $this->username . '&money=0' . '&orderid=' . $this->order_id . '&ip=' . $loginIp . '&lineCode=' . $line_code . '&KindID=0';

            $timestamp = (int) Carbon::now()->valueOf();

            $key = md5($this->agent . $timestamp . $this->md5_key);

            $this->aes_algorithm->setKey($this->aes_key);

            $params = $this->aes_algorithm->encrypt($params);

            $data = [
                'agent' => $this->agent,
                'timestamp' => $timestamp,
                'param' => $params,
                'key' => $key,
            ];

            $client = new Client();

            $response = $client->get($this->base_url . '/channelHandle', [
                'query' => $data,
                'headers' => $this->headers,
                // 'on_stats' => function (TransferStats $stats) {
                //     dd([
                //         'request' => $stats->getRequest(),
                //     ]);
                // },
            ]);

            return $response->getBody()->getContents();

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('AIS Provider Call loginToGame API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function registerToGame($language, $loginIp): ?string
    {
        return 'NOT_SUPPORTED';
    }

    public function depositPoints($amount, $loginIp, $transferNo): ?string
    {
        try {

            $this->order_id = $this->aes_algorithm->getOrderId($this->agent) . $this->username;

            $this->transaction_no = $this->order_id;

            $params = 's=2' . '&account=' . $this->username . '&money=' . $amount . '&orderid=' . $this->order_id;

            $timestamp = (int) Carbon::now()->valueOf();

            $key = md5($this->agent . $timestamp . $this->md5_key);

            $this->aes_algorithm->setKey($this->aes_key);

            $params = $this->aes_algorithm->encrypt($params);

            $data = [
                'agent' => $this->agent,
                'timestamp' => $timestamp,
                'param' => $params,
                'key' => $key,
            ];

            $client = new Client();

            $response = $client->get($this->base_url . '/channelHandle', [
                'query' => $data,
                'headers' => $this->headers,
            ]);

            return $response->getBody()->getContents();

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('AIS Provider Call depositPoints API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function withdrawPoints($amount, $loginIp, $transferNo): ?string
    {
        try {

            $this->order_id = $this->aes_algorithm->getOrderId($this->agent) . $this->username;

            $this->transaction_no = $this->order_id;

            $params = 's=3' . '&account=' . $this->username . '&money=' . $amount . '&orderid=' . $this->order_id;

            $timestamp = (int) Carbon::now()->valueOf();

            $key = md5($this->agent . $timestamp . $this->md5_key);

            $this->aes_algorithm->setKey($this->aes_key);

            $params = $this->aes_algorithm->encrypt($params);

            $data = [
                'agent' => $this->agent,
                'timestamp' => $timestamp,
                'param' => $params,
                'key' => $key,
            ];

            $client = new Client();

            $response = $client->get($this->base_url . '/channelHandle', [
                'query' => $data,
                'headers' => $this->headers,
            ]);

            return $response->getBody()->getContents();

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('AIS Provider Call withdrawPoints API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function checkGameTransactionStatus(): ?string
    {
        try {

            $params = 's=4' . '&orderid=' . $this->order_id;

            $timestamp = (int) Carbon::now()->valueOf();

            $key = md5($this->agent . $timestamp . $this->md5_key);

            $this->aes_algorithm->setKey($this->aes_key);

            $params = $this->aes_algorithm->encrypt($params);

            $data = [
                'agent' => $this->agent,
                'timestamp' => $timestamp,
                'param' => $params,
                'key' => $key,
            ];

            $client = new Client();

            $response = $client->get($this->base_url . '/channelHandle', [
                'query' => $data,
                'headers' => $this->headers,
            ]);

            return $response->getBody()->getContents();

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('AIS Provider Call checkGameTransactionStatus API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }


    public function getBetHistory($start_time): ?string
    {
        try {

            $this->base_url = Config::get('app.v8_record_base_url');

            $start_time = Carbon::parse($start_time);

            $end_time = $start_time->copy()->addMinutes(60);

            $start_time_timestamp = (int) $start_time->getPreciseTimestamp(3);

            $end_time_timestamp = (int) $end_time->getPreciseTimestamp(3);

            $params = 's=6' . '&startTime=' . $start_time_timestamp . '&endTime=' . $end_time_timestamp;

            $timestamp = (int) Carbon::now()->valueOf();

            $key = md5($this->agent . $timestamp . $this->md5_key);

            $this->aes_algorithm->setKey($this->aes_key);

            $params = $this->aes_algorithm->encrypt($params);

            $data = [
                'agent' => $this->agent,
                'timestamp' => $timestamp,
                'param' => $params,
                'key' => $key,
            ];

            $client = new Client();

            $response = $client->get($this->base_url . '/getRecordHandle', [
                'query' => $data,
                'headers' => $this->headers,
            ]);

            return $response->getBody()->getContents();

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('AIS Provider Call getBetHistory API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function getBalance($loginIp): ?string
    {
        try {

            $params = 'account=' . $this->username . '&s=7';

            $timestamp = (int) Carbon::now()->valueOf();

            $key = md5($this->agent . $timestamp . $this->md5_key);

            $this->aes_algorithm->setKey($this->aes_key);

            $params = $this->aes_algorithm->encrypt($params);

            $data = [
                'agent' => $this->agent,
                'timestamp' => $timestamp,
                'param' => $params,
                'key' => $key,
            ];

            $client = new Client();

            $response = $client->get($this->base_url . '/channelHandle', [
                'query' => $data,
                'headers' => $this->headers,
                // 'on_stats' => function (TransferStats $stats) {
                //     dd([
                //         'request' => $stats->getRequest(),
                //     ]);
                // },
            ]);

            return $response->getBody()->getContents();

        } catch (Exception $exception) {
            Log::info('***************************************************************************************');
            Log::info('AIS Provider Call withdrawPoints API Exception');
            Log::info($exception);
            Log::info('***************************************************************************************');
            return null;
        }
    }

    public function getTransactionNo(): ?string
    {
        return $this->transaction_no;
    }

}