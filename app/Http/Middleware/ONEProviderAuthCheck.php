<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use App\Constants\GamePlatformConstants;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Providers\ONEProvider\ONEProvider;

class ONEProviderAuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $api_signature = $request->header('X-Signature');
        
        $one_currency = $request->route('currency'); // its passed as an ONECurrencyEnums

        if (!ONEProvider::authorizeProvider($api_signature, $request->getContent(), $one_currency)) {

            $result = ONEProvider::authFailed($request->traceId);

            $response = response()->json($result->response, $result->status_code);

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_ONE)->first();

            ApiHit::createApiHitEntry(
                $request,
                $response,
                null,
                null,
                $game_platform ?? null,
            );

            return $response;
        }

        return $next($request);
    }
}
