<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\GameItem;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use App\Constants\GamePlatformConstants;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Providers\VIAProvider\VIAProvider;
use App\Services\Providers\VIAProvider\Enums\VIACurrencyEnums;

class VIAProviderAuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $auth_header = $request->header("authorization");

        $via_currency = $request->route('currency'); // its passed as a VIACurrencyEnums

        if (!VIAProvider::authorizeProvider($auth_header, $via_currency)) {

            $result = VIAProvider::authFailed();

            $response = response()->json($result->response, $result->status_code);

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_VIA)->first();

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
