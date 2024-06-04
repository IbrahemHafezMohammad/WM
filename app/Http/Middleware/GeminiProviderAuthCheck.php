<?php

namespace App\Http\Middleware;

use App\Constants\GamePlatformConstants;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use App\Services\Providers\GeminiProvider\Enums\GeminiCurrencyEnums;
use App\Services\Providers\GeminiProvider\GeminiProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class GeminiProviderAuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $seamless_secret    =   $request->input("token");
        $currency           =   $request->route('currency');

        if (!GeminiProvider::authorizeProvider($seamless_secret)) 
        {
            $result         =   GeminiProvider::authFailed();
            $response       =   response()->json($result->response, $result->status_code);
            $game_platform  =   GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_GEMINI)->first();

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
