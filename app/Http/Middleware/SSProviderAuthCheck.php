<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use App\Constants\GamePlatformConstants;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Providers\SSProvider\SSProvider;

class SSProviderAuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pass_key = $request->header('passkey');
        
        $ss_currency = $request->route('currency'); // its passed as an SSCurrencyEnums

        if (!SSProvider::authorizeProvider($pass_key, $ss_currency)) {

            $result = SSProvider::authFailed();

            $response = response()->json($result->response, $result->status_code);

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_SS)->first();

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
