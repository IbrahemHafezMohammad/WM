<?php

namespace App\Http\Controllers;

use App\Models\ApiHit;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Constants\GamePlatformConstants;
use App\Http\Requests\DS88WalletAccessRequest;
use App\Services\Providers\DS88Provider\DS88Provider;
use App\Services\Providers\DS88Provider\Enums\DS88ActionsEnums;
use App\Services\Providers\DS88Provider\Enums\DS88CurrencyEnums;

class DS88Controller extends Controller
{
    public function walletAccess(DS88WalletAccessRequest $request, DS88CurrencyEnums $currency, DS88ActionsEnums $wallet_action, ?string $account = null)
    {
        DB::beginTransaction();

        $exception_detail = null;

        $response = null;

        try {
            
            $validated = $request->validated();

            $result = DS88Provider::walletAccess($validated['all_data'] ?? null, $wallet_action, $currency, $account);

            $response = response($result->response, $result->status_code);
        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('DS88 walletAccess Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = DS88Provider::unknownError();

            $response = response()->json($result->response, $result->status_code);
        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_DS88)->first();

            $extra_data = [
                'jwt_encoded_data' => $request->getContent(),
            ];

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
