<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserPaymentMethod;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\LogService\AdminLogService;
use App\Http\Requests\StoreUserPaymentMethodRequest;
use App\Http\Requests\UpdateUserPaymentMethodRequest;
use App\Http\Requests\RegisterUserPaymentMethodRequest;

class UserPaymentMethodController extends Controller
{
    public function create(RegisterUserPaymentMethodRequest $request)
    {
        UserPaymentMethod::create($request->getUserPaymentMethodData());

        return response()->json([
            'status' => true,
            'message' => 'USER_PAYMENT_METHOD_CREATED_SUCCESSFULLY'
        ], 200);
    }

    public function store(StoreUserPaymentMethodRequest $request)
    {
        UserPaymentMethod::create($request->getUserPaymentMethodData());

        return response()->json([
            'status' => true,
            'message' => 'USER_PAYMENT_METHOD_CREATED_SUCCESSFULLY'
        ], 200);
    }

    public function update(UpdateUserPaymentMethodRequest $request, UserPaymentMethod $user_payment_method)
    {
        if ($user_payment_method->update($request->getUserPaymentMethodData())) {
            AdminLogService::createLog('User Payment Method ' . $user_payment_method->account_name . 'is updated');

            return response()->json([
                'status' => true,
                'message' => 'USER_PAYMENT_METHOD_UPDATED_SUCCESSFULLY'
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'USER_PAYMENT_METHOD_UPDATE_FAILED',
        ], 400);
    }

    public function toggleStatus(UserPaymentMethod $user_payment_method)
    {
        if (!Auth::user()->can('User Payment Method Status Toggle')) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_DENIED'
            ], 403);
        }

        if ($user_payment_method->update(['is_active' => !$user_payment_method->is_active])) {
            AdminLogService::createLog('User Payment Method ' . $user_payment_method->account_name . ' is_active is updated');
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

    public function delete(UserPaymentMethod $user_payment_method)
    {
        $user = Auth::user();

        if ($user->admin && !$user->can('User Payment Method Status Toggle')) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_DENIED'
            ], 403);

        } elseif (($user->player || $user->agent) &&
            !$user->userPaymentMethods->contains('id', $user_payment_method->id)
        ) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_DENIED'
            ], 403);
        }

        if ($user_payment_method->delete()) {

            return response()->json([
                'status' => true,
                'message' => 'USER_PAYMENT_METHOD_DELETED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'USER_PAYMENT_METHOD_DELETE_FAILED'
        ], 400);
    }

    public function listUserPaymentMethods()
    {
        $user = Auth::user();

        return $user->userPaymentMethods()->with('bankCode:id,public_name')->where('is_active', true)->where('currency', $user->player->wallet->currency)->orderByDesc('id')->take(3)->get();
    }
}
