<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Models\Player;
use App\Models\GameItem;
use App\Models\ApiHit;
use Illuminate\Support\Str;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\PlayerBalanceHistory;
use App\Models\GameTransactionHistory;
use App\Constants\GamePlatformConstants;
use App\Exceptions\ControlledExitException;
use App\Http\Requests\KMGetPlayerBalanceRequest;
use App\Services\Providers\KMProvider\KMProvider;
use App\Constants\GameTransactionHistoryConstants;
use App\Http\Requests\KMDebitPlayerBalanceRequest;
use App\Http\Requests\KMCreditPlayerBalanceRequest;
use App\Http\Requests\KMRewardPlayerBalanceRequest;

class KMController extends Controller
{
    public function getPlayerBalance(KMGetPlayerBalanceRequest $request)
    {
        DB::beginTransaction();

        $exception_detail = null;

        $response = [];

        try {

            $validated = $request->validated();

            $game_users = $validated['users'];

            $result = KMProvider::walletBalance($game_users);

            $response = response()->json($result->response, $result->status_code);

        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('KM getPlayerBalance Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = KMProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);

        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_KM)->first();

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

    public function rewardPlayerBalance(KMRewardPlayerBalanceRequest $request)
    {
        DB::beginTransaction();

        $exception_detail = null;

        $response = [];

        try {

            $validated = $request->validated();

            $transactions = $validated['transactions'];

            $result = KMProvider::walletReward($transactions);

            $response = response()->json($result->response, $result->status_code);

        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('KM rewardPlayerBalance Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = KMProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);

        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_KM)->first();

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


    public function debitPlayerBalance(KMDebitPlayerBalanceRequest $request)
    {
        DB::beginTransaction();

        $exception_detail = null;

        $response = [];

        try {

            $validated = $request->validated();

            $transactions = $validated['transactions'];

            $result = KMProvider::walletChangeBalance($transactions, true);

            $response = response()->json($result->response, $result->status_code);

        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('KM debitPlayerBalance Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = KMProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);

        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_KM)->first();

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

    public function creditPlayerBalance(KMCreditPlayerBalanceRequest $request)
    {
        DB::beginTransaction();

        $exception_detail = null;

        $response = [];

        try {

            $validated = $request->validated();

            $transactions = $validated['transactions'];

            $result = KMProvider::walletChangeBalance($transactions, false);

            $response = response()->json($result->response, $result->status_code);

        } catch (\Throwable $exception) {

            $exception_detail = $exception;

            DB::rollback();

            Log::info('######################################################################################');
            Log::info('KM creditPlayerBalance Exception');
            Log::info($exception);
            Log::info('######################################################################################');

            $result = KMProvider::unknownError();

            $response = response()->json($result->response, $result->status_code);

        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            $game_platform = GamePlatform::where('platform_code', GamePlatformConstants::PLATFORM_KM)->first();

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
