<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Services\WebService\WebRequestService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiHit extends Model
{
    use HasFactory;

    protected $fillable = [
        'request',
        'response',
        'api_endpoint',
        'extra_data',
        'request_method',
        'authorization',
        'user_agent',
        'referer',
        'content_type',
        'ip_address',
        'status_code',
        'exception',
        'game_item_id',
        'game_platform_id',
        'payment_method',
        'request_start_timestamp_ms',
        'request_end_timestamp_ms',
        'duration_ms',
    ];

    public function gameItem(): BelongsTo
    {
        return $this->belongsTo(GameItem::class, 'game_item_id');
    }

    public function gamePlatform(): BelongsTo
    {
        return $this->belongsTo(GamePlatform::class, 'game_platform_id');
    }

    public static function createApiHitEntry(
        $request,
        $response,
        $exception,
        $game_item,
        $game_platform,
        $payment_method = null,
        $extra_data = null
    ) {
        $web_request_service = new WebRequestService($request);
        $ip_address = $web_request_service->getIpAddress();

        $authorization = json_encode($request->headers->all());

        self::create([
            'request' => json_encode($request->all()),
            'response' => json_encode($response?->getContent()),
            'extra_data' => json_encode($extra_data),
            'api_endpoint' => $request->fullUrl(),
            'request_method' => $request->method(),
            'authorization' => $authorization,
            'user_agent' => $request->header('User-Agent'),
            'referer' => $request->header('Referer'),
            'content_type' => $request->header('Content-Type'),
            'ip_address' => $ip_address,
            'status_code' => $response?->status(),
            'exception' => $exception,
            'game_item_id' => $game_item?->id,
            'game_platform_id' => $game_platform?->id,
            'payment_method_id' => $payment_method,
            'request_start_timestamp_ms' => LARAVEL_START,
            'request_end_timestamp_ms' => microtime(true) ,
            'duration_ms' => round(microtime(true) - LARAVEL_START, 6),
        ]);
    }
}
