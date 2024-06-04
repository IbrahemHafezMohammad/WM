<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethodHistory;
use Illuminate\Support\Facades\Auth;
use App\Services\LogService\AdminLogService;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\AdjustPaymentMethodRequest;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentMethodController extends Controller
{
    public function store(StorePaymentMethodRequest $request)
    {
        try
        {     
            Log::info("store",[$request->all()]);
            PaymentMethod::create($request->getPaymentMethodData());
            AdminLogService::createLog('new Payment Method ' . $request->account_name . 'is added to the system ');
    
            return response()->json([
                'status' => true,
                'message' => 'PAYMENT_METHOD_CREATED_SUCCESSFULLY'
            ], 200);
        }
        catch(Exception $e)
        {
            return response()->json([
                'status' => false,
                'message' => 'FAILED'
            ], 200);

            Log::info("==============payment method==========================");
            Log::info($e);
            Log::info("======================================================");
        }
    }

    public function update(StorePaymentMethodRequest $request, PaymentMethod $payment_method)
    {
        Log::info("update",[$request->all()]);
        try
        {  
            if ($payment_method->update($request->getPaymentMethodData())) {
                AdminLogService::createLog('Payment Method ' . $payment_method->account_name . 'is updated');

                return response()->json([
                    'status' => true,
                    'message' => 'PAYMENT_METHOD_UPDATED_SUCCESSFULLY'
                ], 200);
            }
            return response()->json([
                'status' => false,
                'message' => 'PAYMENT_METHOD_UPDATE_FAILED',
            ], 400);
        }
        catch(Exception $e)
        {
            return response()->json([
                'status' => false,
                'message' => 'FAILED'
            ], 200);

            Log::info("==============payment method==========================");
            Log::info($e);
            Log::info("======================================================");
        }

    }

    public function index()
    {
        if (!Auth::user()->can('View Payment Methods')) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_DENIED'
            ], 403);
        }

        return PaymentMethod::orderByDesc('id')->get();
    }

    public function toggleDeposit(PaymentMethod $payment_method)
    {
        if (!Auth::user()->can('Adjust Payment Method Settings')) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_DENIED'
            ], 403);
        }

        if ($payment_method->update(['allow_deposit' => !$payment_method->allow_deposit])) {
            AdminLogService::createLog('Bank ' . $payment_method->account_name . ' allow Deposit is updated');

            return response()->json([
                'status' => true,
                'message' => 'DEPOSIT_TOGGLED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'DEPOSIT_TOGGLED_FAILED'
        ], 400);

    }

    public function toggleWithdraw(PaymentMethod $payment_method)
    {
        if (!Auth::user()->can('Adjust Payment Method Settings')) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_DENIED'
            ], 403);
        }

        if ($payment_method->update(['allow_withdraw' => !$payment_method->allow_withdraw])) {
            AdminLogService::createLog('Bank ' . $payment_method->account_name . ' allow Withdraw is updated');
            return response()->json([
                'status' => true,
                'message' => 'WITHDRAW_TOGGLED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'WITHDRAW_TOGGLED_FAILED'
        ], 400);
    }

    public function toggleMaintenance(PaymentMethod $payment_method)
    {
        if (!Auth::user()->can('Adjust Payment Method Settings')) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_DENIED'
            ], 403);
        }

        if ($payment_method->update(['under_maintenance' => !$payment_method->under_maintenance])) {
            AdminLogService::createLog('Bank ' . $payment_method->account_name . ' under maintenance is updated');
            return response()->json([
                'status' => true,
                'message' => 'UNDER_MAINTENANCE_TOGGLED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'UNDER_MAINTENANCE_TOGGLED_FAILED'
        ], 400);
    }

    public function adjust(AdjustPaymentMethodRequest $request)
    {
        $validated = $request->validated();

        $payment_method = PaymentMethod::find($validated['payment_method_id']);

        if (!($old_balance = $payment_method->adjustBalance($validated['isWithdraw'], $validated['amount']))) {

            return response()->json([
                'status' => false,
                'message' => 'LACK_BALANCE_CANT_WITHDRAW'
            ], 400);
        }

        PaymentMethodHistory::adjustBalance($validated['amount'], $validated['isWithdraw'], $validated['remark'], $old_balance, Auth::user()->id, $payment_method);

        return response()->json([
            'status' => true,
            'message' => 'ADJUSTED_SUCCESSFULLY'
        ]);

    }

    public function dropDown()
    {
        return PaymentMethod::all();
        // $payment_methods = PaymentMethod::all();
        // return $payment_methods->map(function ($payment_method) {
        //     return [
        //         'id' => $payment_method->id,
        //         'account_name' => $payment_method->account_name,
        //         'account_number' => $payment_method->account_number,
        //         'public_name' => $payment_method->public_name,
        //         'currency' ,
        //         'payment_code' ,
        //         'under_maintenance' , 
        //         'allow_withdraw' ,
        //         'allow_deposit'
        //     ];
        // });
    }

    public function getDepositBanks()
    {
        $player = Auth::user()->player;
        return PaymentMethod::getDepositBanks($player->wallet->currency)->get();
    }


    public function updateDefault(Request $request, $id)
    {
        $request->validate([
            'is_default' => 'required|boolean',
        ]);

        $isDefault = $request->input('is_default');
        if ($isDefault) {
            PaymentMethod::where('is_default', true)->update(['is_default' => false]);
        }

        $paymentMethod = PaymentMethod::findOrFail($id);
        $paymentMethod->is_default = $isDefault;
        $paymentMethod->save();

        return response()->json([
            'message' => 'Payment method updated successfully.',
            'payment_method' => $paymentMethod,
        ], 200);
    }
}
    
