<?php

namespace App\Http\Controllers;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Constants\GamePlatformConstants;
use App\Http\Requests\ONEWalletAccessRequest;
use App\Services\Providers\ONEProvider\ONEProvider;
use App\Services\Providers\ONEProvider\Enums\ONEActionsEnums;
use App\Services\Providers\ONEProvider\Enums\ONECurrencyEnums;

class ONEController extends Controller
{
    public function walletAccess(ONEWalletAccessRequest $request, ONECurrencyEnums $currency, ONEActionsEnums $wallet_action)
    {
        DB::beginTransaction();

        $exception_detail = null;

        $response = null;

        $extra_data = [];

        try {

            $validated = $request->validated();

            $result = ONEProvider::walletAccess($validated, $wallet_action, $currency);

            $response = response($result->response, $result->status_code);

            $extra_data = $result->extra_data;
        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('ONE walletAccess Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = ONEProvider::unknownError($request->traceId);

            $response = response()->json($result->response, $result->status_code);
        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_ONE)->first();

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

        return $response;
    }
}
