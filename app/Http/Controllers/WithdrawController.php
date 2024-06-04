<?php

namespace App\Http\Controllers;

use Throwable;
use Ramsey\Uuid\Uuid;
use App\Models\ApiHit;
use App\Models\Withdraw;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Events\TransactionCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Events\TransactionChangeEvent;
use App\Constants\TransactionConstants;
use App\Http\Requests\withdrawFaLockRequest;
use App\Http\Requests\withdrawRiskLockRequest;
use App\Services\PaymentService\PaymentService;
use App\Services\PaymentService\PaymentServiceEnum;
use App\Http\Requests\CreateWithdrawTransactionRequest;
use App\Services\PaymentService\DTOs\WithdrawCallbackDTO;
use App\Http\Requests\ApproveRejectTransactionRiskRequest;
use App\Http\Requests\ApproveRejectWithdrawTransactionRequest;
use App\Models\BetRound;
use App\Models\Setting;
use App\Services\PaymentService\Constants\PaymentServiceConstant;
use Carbon\Carbon;
use Exception;

class WithdrawController extends Controller
{

    const MAX_WITHDRAWAL_LIMIT = 50000;
    const MIN_WITHDRAWAL_LIMIT = 100;

    public function withdraw(CreateWithdrawTransactionRequest $request)
    {
        $user = Auth::user();
        $lock = Cache::lock("WITHDRAW_REQUEST_" . $user->id, 5);


        if (!$lock->get()) {
            return response()->json([
                'status' => false,
                'message' => 'PENDING_TRANSACTION'
            ], 403);
        }

        $player = $user->player;
        $validated = $request->validated();
        if (!$player->allow_withdraw || !$player->active) {
            $lock->release();
            return response()->json([
                'status' => false,
                'message' => 'ADMIN_FORBIDDEN'
            ], 403);
        }

        $pending_transaction = Transaction::pendingOrProcessing()->firstWhere('player_id', $player->id);
        if ($pending_transaction) {
            $lock->release();
            return response()->json([
                'status' => false,
                'message' => 'PENDING_TRANSACTION'
            ], 403);

        }

        if ($validated['amount'] < self::MIN_WITHDRAWAL_LIMIT || $validated['amount'] > self::MAX_WITHDRAWAL_LIMIT) {
            $lock->release();
            return response()->json([
                'status' => false,
                'message' => 'INVALID_AMOUNT'
            ], 403);
        }


        if ($player->wallet->balance < $validated['amount']) {
            $lock->release();
            return response()->json([
                'status' => false,
                'message' => 'INSUFFICIENT_BALANCE'
            ], 400);
        } 

        // Fetch the turnover check result
        $turnOverCheck = $this->turnoverCheck($player->id);

        $decodedData = $turnOverCheck->original;
        
        // Ensure turnover check is not null and has the expected properties
        if (!$decodedData['status']) {

            $lock->release();

            return response()->json([
                'status' => false,
                'message' => $decodedData['message']
            ], 403);
        }
        else {
            $player->wallet->debit($validated['amount']);
            $player->wallet->lockBalance($validated['amount']);
        }

        $transaction = Transaction::create($request->getTransactionData());
        //IF TRANSACTION CREATED SUCCESSFULLY THEN CREATE A NEW IS_WITHDRAW RECORD
        if ($transaction) {

            Withdraw::create($request->getWithdrawData($transaction->id));
        }
        TransactionCreated::dispatch($transaction);
        $lock->release();

        $settings = $this->getAdminSettings('auto_risk_approval');
        if($settings && $settings->key == 'auto_risk_approval' && $settings->value == "true")
        {
            $data = [
                'risk_action_status'    =>  1,
                'risk_action_by'        =>  NULL,
                'risk_action_note'      =>  "Risk Auto Approved by system",
                'is_risk_locked'        =>  1,  
            ];

            $this->riskApprove($transaction,$data);

            $financialSettings = $this->getAdminSettings('auto_finance_approval');
            if($financialSettings && $financialSettings->key == 'auto_finance_approval' && $financialSettings->value == "true")
            {
                $paymentGateway = DB::table('user_payment_methods')
                                    ->select('payment_methods.id as payment_id')
                                    ->join('payment_methods', 'user_payment_methods.bank_code_id', '=', 'payment_methods.bank_code_id')
                                    ->join('transactions', 'user_payment_methods.id', '=', 'transactions.user_payment_method_id')
                                    ->where('transactions.isWithdraw', 1)
                                    ->where('payment_methods.is_default', 1)
                                    ->where('transactions.user_payment_method_id', $transaction->user_payment_method_id)
                                    ->first();

                
                $paymentId = $paymentGateway->payment_id;
                if(!$paymentGateway)
                {
                    $paymentGateway = PaymentMethod::select('id')->where('is_default',1)->first();
                    $paymentId = $paymentGateway->id;
                }
                $financialData = [
                    'customer_message'  => "Finance Auto approved by the System",
                    'payment_method_id' => $paymentId,
                    'remark'            => "Finance Auto approved by the System",
                    'status'            => 1,
                ];
                $this->autoFinance($financialData,$transaction);
            }
        }

       

        return response()->json([
            'status' => true,
            'message' => 'WITHDRAW_SUCCESSFULLY'
        ], 200);
    }

    public function turnoverCheck($playerId)
    {
        try {
            // Get the date of the last withdrawal
            $lastWithdrawalDate = Transaction::select('created_at')
                                    ->where('player_id', $playerId)
                                    ->where('isWithdraw', 1)
                                    ->where('status', 1) 
                                    ->orderBy('created_at', 'DESC')
                                    ->first();

            if (!$lastWithdrawalDate) {
                // If the user has no withdrawals, get the date of the first transaction
                $lastWithdrawalDate = Transaction::select('created_at')
                                        ->where('player_id', $playerId)
                                        ->where('status', 1) 
                                        ->orderBy('created_at', 'ASC')
                                        ->first();
                    
            }

            if ($lastWithdrawalDate) {
                $lastWithdrawalDateStr = $lastWithdrawalDate->created_at->toDateTimeString();
                $todayDateStr = Carbon::now()->endOfDay()->toDateTimeString();

                // Fetch sum of total turnover between the last withdrawal date and today
                $totalTurnoverSum = BetRound::where('player_id', $playerId)
                                    ->whereBetween('created_at', [$lastWithdrawalDateStr, $todayDateStr])
                                    ->sum('total_turnovers');

                // Fetch sum of total deposit between last withdrawal date and today
                $totalDepositSum = Transaction::where('player_id', $playerId)
                                    ->where('status', 1) 
                                    ->where('isWithdraw', 0)
                                    ->whereBetween('created_at', [$lastWithdrawalDateStr, $todayDateStr])
                                    ->sum('amount');

                // Check if total turnover sum is >= total deposit sum
                if ($totalTurnoverSum >= $totalDepositSum) {

                    return response()->json([
                        'status'            =>  true,
                        'message'           =>  "ELIGIBLE FOR WITHDRAW",
                        'totalTurnoverSum'  =>  (int)$totalTurnoverSum,
                        'totalDepositSum'   =>  (int)$totalDepositSum,
                        'lastWithdrawDate'  =>  $lastWithdrawalDateStr
                    ], 200);
                } else {
                    return response()->json([
                        'status'    =>  false,
                        'message'   =>  "NOT ELIGIBLE FOR WITHDRAW",
                    ], 400);
                }
            } else {

                return response()->json([
                    'status'    =>  false,
                    'message'   =>  "NO TRANSACTION DATA AVAILABLE",
                ], 400);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  $th->getMessage(),  // Improved error message
            ], 500);  // Use 500 status code for server errors
        }
    }

    public function getAdminSettings($key)
    {
        // $getSettings = ['auto_risk_approval','auto_risk_approval'];
        return Setting::select('key','value')->where('key',$key)->first();
    }
    public function faLockWithdraw(withdrawFaLockRequest $request, $id)
    {
        if (!Auth::user()->hasPermissionTo('FA Lock Withdraw')) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_DENIED',
            ], 403);
        }

        $transaction = Transaction::findOrFail($id);
        if ($transaction->isWithdrawTransaction->risk_action_status != TransactionConstants::RISK_ACTION_APPROVED) {
            return response()->json([
                'status' => false,
                'message' => 'RISK_ACTION_NOT_APPROVED',
            ]);
        }
        $isWithdraw = $transaction->isWithdrawTransaction;
        if ($request->is_fa_locked == $isWithdraw->is_fa_locked) {
            return response()->json([
                'status' => false,
                'faLockedBy' => $isWithdraw->faLockedBy,
                'message' => ((bool) $request->is_fa_locked) ? 'FA_LOCKED_FAILED' : 'FA_UNLOCKED_FAILED',
            ]);
        }
        if ($isWithdraw->update($request->getWithdrawData())) {
            $isWithdraw->refresh();
            return response()->json([
                'status' => true,
                'faLockedBy' => $isWithdraw->faLockedBy,
                'message' => ((bool) $request->is_fa_locked) ? 'FA_LOCKED_SUCCESSFULLY' : 'FA_UNLOCKED_SUCCESSFULLY',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'faLockedBy' => $isWithdraw->faLockedBy,
                'message' => 'FA_LOCKED_FAILED',
            ]);
        }
    }
    public function riskApprove($transaction,$request)
    {
        try {
            $withdraw = $transaction->isWithdrawTransaction;
            if ($withdraw->update($request)) {
    
                if ($request['risk_action_status'] === TransactionConstants::RISK_ACTION_APPROVED) {
                    $withdraw->risk_action_status = TransactionConstants::RISK_ACTION_APPROVED;
                    $transaction->status = TransactionConstants::STATUS_PENDING;
                    $withdraw->save();
                    $transaction->save();
                }
                else if ($request['risk_action_status'] === TransactionConstants::RISK_ACTION_APPROVED) {

                    $transaction->status = TransactionConstants::STATUS_PENDING;
                    $withdraw->risk_action_status = TransactionConstants::RISK_ACTION_REJECTED;
                    $withdraw->save();
                    $transaction->save();
                }
    
                return true;
            }
            
        } catch (Exception $th) {
            Log::info("risk approve debug",[$th]);
            return response()->json([
                'status' => false,
                'message' => 'TRANSACTION_RISK_UPDATE_FAILED',
            ], 400);
        }
    }

    public function approveRejectRisk(ApproveRejectTransactionRiskRequest $request, Transaction $transaction)
    {
        $validated = $request->validated();

        // $this->riskApprove($transaction,$request);
        $withdraw = $transaction->isWithdrawTransaction;
        if ($withdraw->update($request->getTransactionData())) {
            if ($validated['risk_action_status'] === TransactionConstants::RISK_ACTION_REJECTED) {

                $transaction->player->wallet->credit($transaction->amount);
                $transaction->player->wallet->removeLockedBalance($transaction->amount);
                $withdraw->risk_action_status = TransactionConstants::RISK_ACTION_REJECTED;
                $transaction->status = TransactionConstants::STATUS_REJECTED;
                $withdraw->save();
                $transaction->save();
            } elseif ($validated['risk_action_status'] === TransactionConstants::RISK_ACTION_APPROVED) {

                $transaction->status = TransactionConstants::STATUS_PENDING;
                $withdraw->risk_action_status = TransactionConstants::RISK_ACTION_APPROVED;
                $withdraw->save();
                $transaction->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'TRANSACTION_RISK_UPDATED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'TRANSACTION_RISK_UPDATE_FAILED',
        ], 400);
    }
    public function riskLockWithdraw(withdrawRiskLockRequest $request, $id)
    {
        $isWithdraw = Withdraw::where('transaction_id', $id)->first();
        if ($isWithdraw->is_risk_locked == $request->is_risk_locked) {
            return response()->json([
                'status' => false,
                'message' => ((bool) $request->is_fa_locked) ? 'RISK_LOCKED_FAILED' : 'Risk_UNLOCKED_FAILED',
            ]);
        }
        if ($isWithdraw->update($request->getWithdrawDataForRisk())) {
            return response()->json([
                'status' => true,
                'message' => ((bool) $request->is_risk_locked) ? 'Risk_LOCKED_SUCCESSFULLY' : 'Risk_UNLOCKED_SUCCESSFULLY',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'RISK_LOCKED_FAILED',
            ]);
        }
    }

    //Function will auto approve the finance if auto_finance_approval and auto_risk_approval flag is on
    public function autoFinance($request,$transaction)
    {
        try 
        {
            if ($transaction->firstWithdrawCheck($transaction->player_id) && $request['status'] === TransactionConstants::STATUS_APPROVED) {
                $transaction->isWithdrawTransaction->update(['is_first' => true]);
            };
    
            $transaction->update($request);
    
            if ($request['status'] === TransactionConstants::STATUS_APPROVED) 
            {
    
                $payment_method = PaymentMethod::find($request['payment_method_id']);
                $transaction->update(['payment_method_id' => $request['payment_method_id']]);
                $paymentService = new PaymentService($transaction, $payment_method);
                $withdrawService = $paymentService->getWithdrawPaymentProvider();
    
                if (!$withdrawService) {
                    $transaction->withdrawRequestFailed('WITHDRAW_SERVICE_NOT_FOUND');
                    return response()->json([
                        'status' => false,
                        'message' => 'WITHDRAW_SERVICE_NOT_FOUND',
                    ], 400);
                }
    
                $withdrawDTO = $withdrawService->makeWithdrawRequest();
    
                if ($withdrawDTO->action_status == PaymentServiceConstant::STATUS_SUCCESS) 
                {
                    $transaction->withdrawApproved($payment_method, $validated['remark'] ?? null);
                } 
                else if ($withdrawDTO->action_status == PaymentServiceConstant::WAIT_FOR_SERVICE_PAYMENT) 
                {
                    $transaction->withdrawRequestProcessing($payment_method, $withdrawDTO->reference);
                } 
                else 
                {
                    $transaction->withdrawRequestFailed($withdrawDTO->message, $withdrawDTO->reference);
                }
            } 
            elseif ($request['status'] === TransactionConstants::STATUS_REJECTED) 
            {
                $transaction->player->wallet->removeLockedBalance($transaction->amount);
                $transaction->player->wallet->credit($transaction->amount);
                $transaction->save();
            }
    
            return true;
        } 
        catch (\Throwable $th) 
        {
            Log::info("auto finance debug",[$th]);
                return response()->json([
                    'status' => false,
                    'message' => 'ERROR IN AUTO FINANCE',
                ], 400);
        }
    }
    public function approveRejectWithdraw(ApproveRejectWithdrawTransactionRequest $request, Transaction $transaction)
    {
        $validated = $request->validated();

        // $this->autoFinance($validated,$transaction);

        if ($transaction->firstWithdrawCheck($transaction->player_id) && $validated['status'] === TransactionConstants::STATUS_APPROVED) {
            $transaction->isWithdrawTransaction->update(['is_first' => true]);
        };

        $transaction->update($request->getTransactionData());

        if ($validated['status'] === TransactionConstants::STATUS_APPROVED) {

            $payment_method = PaymentMethod::find($validated['payment_method_id']);
            $transaction->update(['payment_method_id' => $validated['payment_method_id']]);
            $paymentService = new PaymentService($transaction, $payment_method);
            $withdrawService = $paymentService->getWithdrawPaymentProvider();

            if (!$withdrawService) {
                $transaction->withdrawRequestFailed('WITHDRAW_SERVICE_NOT_FOUND');
                return response()->json([
                    'status' => false,
                    'message' => 'WITHDRAW_SERVICE_NOT_FOUND',
                ], 400);
            }

            $withdrawDTO = $withdrawService->makeWithdrawRequest();

            if ($withdrawDTO->action_status == PaymentServiceConstant::STATUS_SUCCESS) {
                $transaction->withdrawApproved($payment_method, $validated['remark'] ?? null);
            } else if ($withdrawDTO->action_status == PaymentServiceConstant::WAIT_FOR_SERVICE_PAYMENT) {
                $transaction->withdrawRequestProcessing($payment_method, $withdrawDTO->reference);
            } else {
                $transaction->withdrawRequestFailed($withdrawDTO->message, $withdrawDTO->reference);
            }
        } elseif ($validated['status'] === TransactionConstants::STATUS_REJECTED) {
            $transaction->player->wallet->removeLockedBalance($transaction->amount);
            $transaction->player->wallet->credit($transaction->amount);
            $transaction->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'TRANSACTION_UPDATED_SUCCESSFULLY',
        ], 200);
    }

    public function withdrawCallback(Request $request, int $service, Transaction $transaction)
    {
        Log::info('###############################  Callback Withdraw  Response   ######################################################', [$request->all()]);
        $service_enum = PaymentServiceEnum::tryFrom($service);

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
            Log::info('=============================================================in withdrawCallback : ================================== ');
            Log::info($request->all());
            $paymentService = new PaymentService($transaction);
            $withdrawService = $paymentService->getWithdrawPaymentProvider();
            $withdrawCallbackDTO = $withdrawService->processWithdrawCallback($request->all());

            Log::info("now the withdraw status iss ", [$withdrawCallbackDTO]);

            Log::info("now the withdraw status iss " . $withdrawCallbackDTO->message);

            if ($withdrawCallbackDTO->status == PaymentServiceConstant::STATUS_SUCCESS) {
                $transaction->withdrawApproved(null, $withdrawCallbackDTO->message);
            } else if ($withdrawCallbackDTO->status == PaymentServiceConstant::STATUS_FAILED) {
                $transaction->withdrawRequestFailed($withdrawCallbackDTO->message);
            }
            $response = response()->json($withdrawCallbackDTO->response_data, $withdrawCallbackDTO->response_status);
        } catch (\Throwable $exception) {
            DB::rollback();
            Log::info('######################################################################################');
            Log::info('withdrawCallback Exception');
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

    public function getTurnoverDetails()
    {
        $playerId = Auth::user()->player->id;
        try {
            // Get the date of the last withdrawal
            $lastWithdrawalDate = Transaction::select('created_at')
                                    ->where('player_id', $playerId)
                                    ->where('isWithdraw', 1)
                                    ->where('status', 1) 
                                    ->orderBy('created_at', 'DESC')
                                    ->first();
           
            if (!$lastWithdrawalDate) {
                // If the user has no withdrawals, get the date of the first transaction

                $lastWithdrawalDate = Transaction::where('player_id',$playerId)
                                ->where('status', 1)
                                ->orderBy('created_at', 'asc')
                                ->first();
                
               
            }

            if ($lastWithdrawalDate && $lastWithdrawalDate != null) {
                $lastWithdrawalDateStr = $lastWithdrawalDate->created_at->toDateTimeString();
                $todayDateStr = Carbon::now()->endOfDay()->toDateTimeString();

                // Fetch sum of total turnover between the last withdrawal date and today
                $totalTurnoverSum = BetRound::where('player_id', $playerId)
                                    ->whereBetween('created_at', [$lastWithdrawalDateStr, $todayDateStr])
                                    ->sum('total_turnovers');

                // Fetch sum of total deposit between last withdrawal date and today
                $totalDepositSum = Transaction::where('player_id', $playerId)
                                    ->where('status', 1) 
                                    ->where('isWithdraw', 0)
                                    ->whereBetween('created_at', [$lastWithdrawalDateStr, $todayDateStr])
                                    ->sum('amount');
                
               
                $remainingTurnOver = (int)$totalDepositSum - (int)$totalTurnoverSum;

                if($remainingTurnOver < 0)
                {
                    $remainingTurnOver = 0;
                }   

                return response()->json([
                    'status'            =>  true,
                    'totalTurnover'     =>  (int)$totalTurnoverSum,
                    'totalDeposit'      =>  (int)$totalDepositSum,
                    'remainingTurnover' =>  (int)$remainingTurnOver,
                ], 200);
            } else {

                return response()->json([
                    'status'            =>  true,
                    'totalTurnover'     =>  0,
                    'totalDeposit'      =>  0,
                    'remainingTurnover' =>  0,
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  $th->getMessage(), 
            ], 500); 
        }
    }
}
