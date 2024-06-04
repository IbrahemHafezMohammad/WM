<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use App\Constants\GamePlatformConstants;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Providers\SABAProvider\SABAProvider;
use App\Services\Providers\SABAProvider\Enums\SABACurrencyEnums;

class SABAProviderAuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $seamless_secret = $request->input("key");

        $currency = $request->route('currency');

        $saba_currency = SABACurrencyEnums::tryFrom($currency);

        if (!SABAProvider::authorizeProvider($seamless_secret, $saba_currency)) {

            $result = SABAProvider::authFailed();

            $response = response()->json($result->response, $result->status_code);

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_SABA)->first();

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
