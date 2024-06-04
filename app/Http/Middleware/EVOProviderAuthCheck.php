<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use App\Constants\GamePlatformConstants;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Providers\EVOProvider\EVOProvider;

class EVOProviderAuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $auth_token = $request->query('authToken');

        $evo_currency = $request->route('currency'); // its passed as an EVOCurrencyEnums

        if (!EVOProvider::authorizeProvider($auth_token, $evo_currency)) {

            $result = EVOProvider::authFailed();

            $response = response()->json($result->response, $result->status_code);

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_EVO)->first();

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
