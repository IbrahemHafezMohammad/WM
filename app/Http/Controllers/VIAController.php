<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Player;
use App\Models\GameItem;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Constants\GamePlatformConstants;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\ControlledExitException;
use App\Http\Requests\VIAChangePlayerBalance;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\VIAGetPlayerBalanceRequest;
use App\Services\Providers\VIAProvider\VIAProvider;
use App\Services\Providers\VIAProvider\Enums\VIACurrencyEnums;

class VIAController extends Controller
{
    public function getPlayerBalance(VIAGetPlayerBalanceRequest $request, VIACurrencyEnums $currency)
    {
        DB::beginTransaction();

        $exception_detail = null;

        try {

            $validated = $request->validated();

            $result = VIAProvider::walletGetBalance($validated['vendorPlayerId'], $currency);

            $response = response()->json($result->response, $result->status_code);

        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('VIA getPlayerBalance Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = VIAProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);

        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }
            
            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_VIA)->first();

            ApiHit::createApiHitEntry(
                $request,
                $response,
                $exception_detail,
                null,
                $game_platform,
            );
        }

        return $response;
    }

    public function changePlayerBalance(VIAChangePlayerBalance $request, VIACurrencyEnums $currency)
    {
        $request_start = now()->valueOf();

        DB::beginTransaction();

        $exception_detail = null;

        $response = null;

        try {

            $validated = $request->validated();

            $result = VIAProvider::walletChangeBalance($validated, $currency);

            $response = response()->json($result->response, $result->status_code);

        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();
            
            Log::info('######################################################################################');
            Log::info('VIA changePlayerBalance Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = VIAProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);

        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_VIA)->first();

            $request_end = now()->valueOf();

            $duration = $request_end - $request_start;

            ApiHit::createApiHitEntry(
                $request,
                $response,
                $exception_detail,
                null,
                $game_platform,
                $request_start,
                $request_end,
                $duration
            );
        }

        return $response;

    }
}
