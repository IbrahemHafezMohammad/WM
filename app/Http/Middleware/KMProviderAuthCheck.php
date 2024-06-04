<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use App\Constants\GamePlatformConstants;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Providers\KMProvider\KMProvider;

class KMProviderAuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $client_id = $request->header("X-QM-ClientId");

        $client_secret = $request->header("X-QM-ClientSecret");

        if (!KMProvider::authorizeProvider($client_id, $client_secret)) {

            $result = KMProvider::authFailed();

            $response = response()->json($result->response, $result->status_code);

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_KM)->first();

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
