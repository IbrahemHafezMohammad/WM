<?php

namespace App\Http\Controllers;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Constants\GamePlatformConstants;
use App\Http\Requests\CMDWalletAccessRequest;
use App\Services\Providers\CMDProvider\CMDProvider;
use App\Services\Providers\CMDProvider\Enums\CMDActionsEnums;
use App\Services\Providers\CMDProvider\Enums\CMDCurrencyEnums;

class CMDController extends Controller
{
    public function walletAccess(CMDWalletAccessRequest $request, CMDCurrencyEnums $currency, CMDActionsEnums $wallet_action)
    {
        DB::beginTransaction();

        $exception_detail = null;

        $response = null;

        $extra_data = [];

        try {

            $validated = $request->validated();

            $result = CMDProvider::walletAccess($validated, $wallet_action, $currency);

            if ($result->response_type == CMDProvider::RESPONSE_TYPE_JSON) {

                $response = response($result->response, $result->status_code)->header('Content-Type', 'text/plain');

            } else {

                $response = response($result->response, $result->status_code)->header('Content-Type', 'application/xml');
            }

            $extra_data = $result->extra_data;

        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('CMD walletAccess Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = CMDProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);

        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_CMD)->first();

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
