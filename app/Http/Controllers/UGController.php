<?php

namespace App\Http\Controllers;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Constants\GamePlatformConstants;
use App\Http\Requests\UGWalletAccessRequest;
use App\Services\Providers\UGProvider\UGProvider;
use App\Services\Providers\UGProvider\Enums\UGActionsEnums;
use App\Services\Providers\UGProvider\Enums\UGCurrencyEnums;

class UGController extends Controller
{
    public function walletAccess(UGWalletAccessRequest $request, UGCurrencyEnums $currency, UGActionsEnums $wallet_action)
    {
        DB::beginTransaction();

        $exception_detail = null;

        $response = [];

        try {

            $validated = $request->validated();
            
            $result = UGProvider::walletAccess($validated, $wallet_action, $currency);

            $response = response()->json($result->response, $result->status_code);
            
        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('UG walletAccess Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = UGProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);

        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_UG)->first();

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
