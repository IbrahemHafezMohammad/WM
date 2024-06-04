<?php

namespace App\Http\Controllers;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Constants\GamePlatformConstants;
use App\Http\Requests\SSWalletAccessRequest;
use App\Services\Providers\SSProvider\SSProvider;
use App\Services\Providers\SSProvider\Enums\SSActionsEnums;
use App\Services\Providers\SSProvider\Enums\SSCurrencyEnums;

class SSController extends Controller
{
    public function walletAccess(SSWalletAccessRequest $request, SSCurrencyEnums $currency, SSActionsEnums $wallet_action)
    {
        DB::beginTransaction();

        $exception_detail = null;

        $response = null;

        $extra_data = [];

        $is_api_hit = true;

        try {

            $validated = $request->validated();

            $result = SSProvider::walletAccess($validated, $wallet_action, $currency);

            $response = response($result->response, $result->status_code);

            $extra_data = $result->extra_data;

            $is_api_hit = $result->is_api_hit;
        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('SS walletAccess Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = SSProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);
        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            if ($is_api_hit) {
                
                $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_SS)->first();

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
