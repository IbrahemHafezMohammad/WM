<?php

namespace App\Http\Controllers;

use App\Models\GameItem;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Constants\GamePlatformConstants;
use App\Http\Requests\AWCWalletAccessRequest;
use App\Services\Providers\AWCProvider\AWCProvider;
use App\Services\Providers\AWCProvider\Enums\AWCCurrencyEnums;

class AWCController extends Controller
{
    public function walletAccess(AWCWalletAccessRequest $request, AWCCurrencyEnums $currency)
    {
        DB::beginTransaction();
        
        $exception_detail = null;

        $response = [];

        try {

            $validated = $request->validated();

            $data = $validated['message'];

            $result = AWCProvider::walletAccess($data, $currency);

            $response = response()->json($result->response, $result->status_code);
            
        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('AWC walletAccess Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = AWCProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);

        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_AWC)->first();

            ApiHit::createApiHitEntry(
                $request,
                $response,
                $exception_detail,
                null,
                $game_platform ?? null
            );
        }

        return $response;
    }
}
