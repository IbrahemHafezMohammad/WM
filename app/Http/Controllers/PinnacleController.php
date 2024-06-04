<?php

namespace App\Http\Controllers;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Constants\GamePlatformConstants;
use App\Http\Middleware\PinnacleProviderIPCheck;
use App\Services\Providers\PinnacleProvider\PinnacleProvider;
use App\Http\Requests\PinnacleProviderWalletAccessRequest;
use App\Services\Providers\PinnacleProvider\Enums\PinnacleActionsEnums;
use App\Services\Providers\PinnacleProvider\Enums\PinnacleCurrencyEnums;

class PinnacleController extends Controller
{
    public function walletAccess(PinnacleProviderWalletAccessRequest $request, PinnacleCurrencyEnums $currency)
    {
        DB::beginTransaction();

        $exception_detail = null;

        $response = null;

        $extra_data = [];

        $is_api_hit = true;

        try {

            $validated = $request->validated();

            $wallet_action = PinnacleActionsEnums::from($request->route()->getName());
            
            $result = PinnacleProvider::walletAccess($validated, $wallet_action, $currency);

            $response = response()->json($result->response, $result->status_code);

            $extra_data = $result->extra_data;

            $is_api_hit = $result->is_api_hit;
        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('Pinnacle walletAccess Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = PinnacleProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);
        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            if ($is_api_hit) {
                
                $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_PINNACLE)->first();

                ApiHit::createApiHitEntry(
                    $request,
                    $response,
                    $exception_detail,
                    null,
                    $game_platform ?? null,
                    null,
                    $extra_data
                );
            }
        }

        return $response;
    }
}
