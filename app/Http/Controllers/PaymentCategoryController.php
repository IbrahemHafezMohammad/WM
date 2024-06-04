<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\LogService\AdminLogService;
use App\Http\Requests\StorePaymentCategoryRequest;
use App\Http\Requests\UpdatePaymentCategoryRequest;

class PaymentCategoryController extends Controller
{
    public function store(StorePaymentCategoryRequest $request)
    {
        PaymentCategory::create($request->getPaymentCategoryData());
        AdminLogService::createLog('new Payment Category ' . $request->name . 'is added to the system ');

        return response()->json([
            'status' => true,
            'message' => 'PAYMENT_METHOD_CREATED_SUCCESSFULLY'
        ], 200);
    }

    public function update(UpdatePaymentCategoryRequest $request, PaymentCategory $payment_category)
    {
        if ($payment_category->update($request->getPaymentCategoryData())) {
            AdminLogService::createLog('Payment Category ' . $request->name . 'is updated to the system ');
            return response()->json([
                'status' => true,
                'message' => 'PAYMENT_CATEGORY_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PAYMENT_CATEGORY_UPDATE_FAILED'
        ], 400);
    }

    public function index()
    {
        return PaymentCategory::orderByDesc('id')->get();
    }

    public function toggleStatus(PaymentCategory $payment_category)
    {
        if (!Auth::user()->can('Adjust Payment Method Settings')) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_DENIED'
            ], 403);
        }

        if ($payment_category->update(['is_enabled' => !$payment_category->is_enabled])) {
            AdminLogService::createLog('Payment Category ' . $payment_category->name . ' status is toggled to ' . $payment_category->is_enabled ? 'enabled' : 'disabled');
            return response()->json([
                'status' => true,
                'message' => 'STATUS_TOGGLED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'STATUS_TOGGLED_FAILED'
        ], 400);
    }

    public function listDepositPaymentCategories()
    {
        $currency = Auth::user()->player->wallet->currency;

        return PaymentCategory::relatedPaymentMethods($currency);
    }

    public function delete(PaymentCategory $payment_category)
    {
        if($payment_category->delete()) {

            return response()->json([
                'status' => true,
                'message' => 'PAYMENT_CATEGORY_DELETED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PAYMENT_CATEGORY_DELETE_FAILED'
        ], 400);
    }

    public function playerListPaymentCategories()
    {
        return PaymentCategory::relatedBankCodes();
    }

    public function dropDown()
    {
        return PaymentCategory::where('is_enabled', true)->orderByDesc('id')->get();
    }
}
