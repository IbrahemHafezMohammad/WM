<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use App\Models\ProviderIPWhitelist;
use App\Constants\GamePlatformConstants;
use App\Services\WebService\WebRequestService;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Providers\ONEProvider\ONEProvider;

class ONEProviderIPCheck
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

        $isAllowed = ProviderIPWhitelist::where('provider_code', GamePlatformConstants::PLATFORM_ONE)
            ->where('ip_address', $request_ip)
            ->exists();

        if (!$isAllowed) {

            $result = ONEProvider::ipNotAllowed($request_ip, $request->traceId);

            $response = response()->json($result->response, $result->status_code);

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_ONE)->first();
            
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
