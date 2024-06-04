<?php

namespace App\Http\Controllers;

use App\Models\GameItem;
use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Constants\GameItemConstants;
use App\Http\Controllers\Controller;
use App\Constants\GamePlatformConstants;
use App\Http\Requests\SABAWalletAccessRequest;
use App\Services\Providers\SABAProvider\SABAProvider;
use App\Services\Providers\SABAProvider\Enums\SABACurrencyEnums;

class SABAController extends Controller
{
    public function walletAccess(SABAWalletAccessRequest $request, int $currency)
    {
        DB::beginTransaction();
        
        $exception_detail = null;

        $response = [];

        try {

            $validated = $request->validated();

            $data = $validated['message'];

            $saba_currency = SABACurrencyEnums::tryFrom($currency);

            $game_item = GameItem::where('game_id', GamePlatformConstants::SABA_GAME_TYPE_SABA)->first();
            
            $result = SABAProvider::walletAccess($game_item, $data, $saba_currency);

            $response = response()->json($result->response, $result->status_code);
            
        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('SABA walletAccess Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = SABAProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);

        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_SABA)->first();

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
