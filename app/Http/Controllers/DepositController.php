<?php

namespace App\Http\Controllers;

use App\Constants\PaymentMethodHistoryConstants;
use App\Models\Deposit;
use App\Models\ApiHit;
use App\Models\Player;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Events\TransactionCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Constants\TransactionConstants;
use App\Http\Requests\DepositFaLockRequest;
use App\Services\PaymentService\PaymentService;
use App\Services\PaymentService\PaymentServiceVO;
use App\Services\PaymentService\PaymentServiceEnum;
use App\Http\Requests\CreateDepositTransactionRequest;
use App\Http\Requests\ApproveRejectDepositTransactionRequest;
use App\Models\PaymentMethodHistory;
use App\Services\PaymentService\Constants\PaymentServiceConstant;
use Illuminate\Support\Facades\Cache;

class DepositController extends Controller
{

    //api for update the is_deposit table
    public function deposit(CreateDepositTransactionRequest $request)
    {
        
        $clientUrl =  $request->header('referer');
        $user = Auth::user();
        $lock = Cache::lock("DEPOSIT_REQUEST_" . $user->id, 5);
        if (!$lock->get()) {
            return response()->json([
                'status' => false,
                'message' => 'PENDING_TRANSACTION'
            ], 403);
        }
        $validated = $request->validated();
        $player = $user->player;

        $pending_transaction = Transaction::pendingOrProcessing()->where('player_id', $player->id)->first();;

        if (!$player->active) {
            return response()->json([
                'status' => false,
                'message' => 'ADMIN_FORBIDDEN'
            ], 403);
        }

        if ($pending_transaction) {
            return response()->json([
                'status' => false,
                'message' => 'PENDING_TRANSACTION'
            ], 403);
        }

        $paymentMethod = PaymentMethod::find($validated['payment_method_id']);
        if ($validated['amount'] < $paymentMethod->min_deposit_amount || $validated['amount'] > $paymentMethod->max_deposit_amount) {
            return response()->json([
                'status' => false,
                'message' => 'INVALID_AMOUNT'
            ], 403);
        }
        $transaction = Transaction::create($request->getTransactionData());

        Deposit::create($request->getDepositData($transaction->id));
        TransactionCreated::dispatch($transaction);

        $paymentService = new PaymentService($transaction);
        $depositService = $paymentService->getDepositPaymentProvider();
        $deposit_dto = $depositService->makeDepositRequest($clientUrl);
        Log::info('Deposit Req and response' . json_encode($deposit_dto));


        if ($deposit_dto->action_status == PaymentServiceConstant::STATUS_FAILED) {
            $transaction->failedPayment($deposit_dto);
            Log::info('Deposit Failed: ' . json_encode($deposit_dto));
            return response()->json([
                'status' => false,
                'message' => 'TRANSACTION_FAILED',
                'test_data'=>$deposit_dto
            ], 403);
        } elseif ($deposit_dto->action_status == PaymentServiceConstant::STATUS_WAIT_FOR_PLAYER_PAYMENT && $deposit_dto->payment_url) {
            $transaction->successPayment($deposit_dto);
            return response()->json([
                'status' => true,
                'link' => $deposit_dto->payment_url,
                'message' => 'WAITING_FOR_PAYMENT',
                'test_data'=>$deposit_dto

            ], 200);
        } elseif ($deposit_dto->action_status == PaymentServiceConstant::STATUS_SUCCESS) {
            Log::info('Coming to success response');
            $transaction->successPayment($deposit_dto);
            Log::info('Coming to success payemnt');
            $transaction->depositApproved($deposit_dto->message);
            Log::info('Coming to DepositApproved');
            $lock->release();
            return response()->json([
                'status' => true,
                'message' => 'SUCCESS',
                'test_data'=>$deposit_dto
                
            ], 200);
        } elseif ($deposit_dto->action_status == PaymentServiceConstant::STATUS_PENDING) {
            $transaction->pendingManualApprove($deposit_dto);
            return response()->json([
                'status' => true,
                'message' => 'PENDING',
                'test_data'=>$deposit_dto

            ], 200);
        }

        $transaction->failedPayment($deposit_dto);
        return response()->json([
            'status' => false,
            'message' => 'FAILED',
            'test_data'=>$deposit_dto
        ], 403);
        //});
    }

    public function faLockDeposit(DepositFaLockRequest $request, $id)
    {

        $transaction = Transaction::findOrFail($id);

        $isDeposit = $transaction->isDepositTransaction;

        if (!$isDeposit) {
            return response()->json([
                'status' => false,
                'fa_locked_by' => null,
                'message' => 'TRNSACTION_IS_NOT_DEPOSIT',
            ]);
        }

        if ($request->is_fa_locked == $isDeposit->is_fa_locked) {
            return response()->json([
                'status' => false,
                'faLockedBy' => $isDeposit->faLockedBy,
                'message' => ((bool) $request->is_fa_locked) ? 'FA_LOCKED_FAILED' : 'FA_UNLOCKED_FAILED',
            ]);
        }
        if ($isDeposit->update($request->getDepositData())) {
            $isDeposit->refresh();
            return response()->json([
                'status' => true,
                'faLockedBy' => $isDeposit->faLockedBy,
                'message' => ((bool) $request->is_fa_locked) ? 'FA_LOCKED_SUCCESSFULLY' : 'FA_UNLOCKED_SUCCESSFULLY',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'fa_locked_by' => $isDeposit->faLockedBy,
                'message' => 'FA_LOCKED_FAILED',
            ]);
        }
    }

    public function approveRejectDeposit(ApproveRejectDepositTransactionRequest $request, Transaction $transaction)
    {
        $validated = $request->validated();


        $transaction->update($request->getTransactionData());

        if ($validated['status'] === TransactionConstants::STATUS_APPROVED) {

            $transaction->depositApproved($validated['remark'] ?? null);
            
        } elseif ($validated['status'] === TransactionConstants::STATUS_REJECTED) {

            $status = TransactionConstants::STATUS_REJECTED;
            $transaction->depositFailed($validated['remark'] ?? null,$status);
        }

        return response()->json([
            'status' => true,
            'message' => 'TRANSACTION_UPDATED_SUCCESSFULLY',
        ], 200);
    }

    public function depositCallback(Request $request, int $service, Transaction $transaction)
    {

        $service_enum = PaymentServiceEnum::tryFrom($service);
        //make sure traNSACTION not already approved

        if (
            $transaction->status == TransactionConstants::STATUS_APPROVED ||
            $transaction->status == TransactionConstants::STATUS_REJECTED ||
            $transaction->status == TransactionConstants::STATUS_PAYMENT_FAILED
        ) {
            return response()->json([
                'status' => false,
                'message' => 'TRANSACTION_ALREADY_COMPLETED',
            ], 403);
        }

        DB::beginTransaction();

        $exception_detail = null;

        $response = null;

        try {
            $paymentService = new PaymentService($transaction);
            $depositService = $paymentService->getDepositPaymentProvider();
            $depositCallbackDTO = $depositService->processDepositCallback($request->all(), $transaction);
            Log::info(["Deposit callback request : ",  $depositCallbackDTO]);
            Log::info("Transaction before check : "[$transaction->status]);
            
            if ($transaction->status !== TransactionConstants::STATUS_WAITING_FOR_PAYMENT) {
                $transaction->depositFailed("Failed Status is Not Wait For Payment : status code Not matching..");
            } else if ($depositCallbackDTO->status) {
                $transaction->depositApproved($depositCallbackDTO->message);
            } else {
                Log::info("Callback Failed: " . $depositCallbackDTO->message);
                Log::info(json_encode($depositCallbackDTO));
                $transaction->depositFailed($depositCallbackDTO->message);
            }
        
            return response()->json($depositCallbackDTO->response_data, $depositCallbackDTO->response_status);
        } catch (\Throwable $exception) {

            $exception_detail = $exception;
            DB::rollback();
            Log::info('######################################################################################');
            Log::info('depositCallback Exception');
            Log::info($exception);
            Log::info('######################################################################################');
            $response = response()->json([
                'status' => false,
                'message' => 'UNKNOWN_ERROR',
            ], 500);
        } finally {

            if (is_null($exception_detail)) {

                DB::commit();
            }

            ApiHit::createApiHitEntry(
                $request,
                $response,
                $exception_detail,
                null,
                null,
                $service_enum?->name
            );
        }

        return $response;
    }
}
