<?php

namespace App\Http\Controllers;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Constants\GamePlatformConstants;
use App\Http\Requests\EVOWalletAccessRequest;
use App\Services\Providers\EVOProvider\EVOProvider;
use App\Services\Providers\EVOProvider\Enums\EVOActionsEnums;
use App\Services\Providers\EVOProvider\Enums\EVOCurrencyEnums;

class EVOController extends Controller
{
    public function walletAccess(EVOWalletAccessRequest $request, EVOCurrencyEnums $currency, EVOActionsEnums $wallet_action)
    {
        DB::beginTransaction();
        
        $exception_detail = null;

        $response = [];

        try {

            $validated = $request->validated();

            $result = EVOProvider::walletAccess($validated, $currency, $wallet_action);

            $response = response()->json($result->response, $result->status_code);
            
        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('EVO walletAccess Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = EVOProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);

        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_EVO)->first();

            ApiHit::createApiHitEntry(
                $request,
                $response,
                $exception_detail,
                null,
                $game_platform ?? null,
            );
        }

        return $response;
    }
}
