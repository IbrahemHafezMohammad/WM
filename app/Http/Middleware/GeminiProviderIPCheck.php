<?php

namespace App\Http\Middleware;

use App\Constants\GamePlatformConstants;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use App\Models\ProviderIPWhitelist;
use App\Services\Providers\GeminiProvider\GeminiProvider;
use App\Services\WebService\WebRequestService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GeminiProviderIPCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $web_request_service = new WebRequestService($request);

        $request_ip = $web_request_service->getIpAddress();

        $isAllowed = ProviderIPWhitelist::where('provider_code', GamePlatformConstants::PLATFORM_GEMINI)
            ->where('ip_address', $request_ip)
            ->exists();

        if (!$isAllowed) {

            $result = GeminiProvider::ipNotAllowed($request_ip);

            $response = response()->json($result->response, $result->status_code);

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_GEMINI)->first();

            ApiHit::createApiHitEntry(
                $request,
                $response,
                null,
                null,
                $game_platform,
            );

            return $response;
        }

        return $next($request);
    }
}
