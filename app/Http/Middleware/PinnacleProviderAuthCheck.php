<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Constants\GamePlatformConstants;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Providers\PinnacleProvider\PinnacleProvider;
use App\Services\Providers\PinnacleProvider\Enums\PinnacleActionsEnums;
use App\Services\Providers\PinnacleProvider\Enums\PinnacleCurrencyEnums;

class PinnacleProviderAuthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log::info('PinnacleProviderAuthCheck middleware');

        // log all request info including the request body and the route it came from
        // Log::info('Request Info: ' . json_encode($request->all()));
        // Log::info('Route Info: ' . json_encode($request->route()));

        $wallet_action = $request->route()->getName();

        if ($wallet_action === PinnacleActionsEnums::PING->value) {
            return $next($request);
        }

        $signature = $request->input("Signature");

        $pn_currency = $request->route('currency');

        if (!PinnacleProvider::authorizeProvider($signature, $pn_currency)) {

            $result = PinnacleProvider::authFailed();

            $response = response()->json($result->response, $result->status_code);

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_PINNACLE)->first();

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
