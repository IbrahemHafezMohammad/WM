<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\WhitelistIP;
use App\Services\LogService\AdminLogService;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\OTPCheckRequest;
use App\Http\Requests\EditAdminRequest;
use App\Http\Requests\ListAdminRequest;
use App\Http\Requests\LoginAdminRequest;
use App\Http\Requests\StoreAdminRequest;
use App\Services\WebService\WebRequestService;
use App\Http\Requests\DisableTwoFactorAuthRequest;

class AdminController extends Controller
{
    public function create(StoreAdminRequest $request)
    {
        $user = User::create($request->getUserData());

        $user->admin()->create();

        if($request->has('role_id') ){
            $role = Role::find($request->role_id);
            if($role){
                Log::info($role->name . " role found");
                $user->assignRole($role->name);
            }
        }
        AdminLogService::createLog('New Admin created with name: ' . $user->user_name);

        return response()->json([
            'status' => true,
            'message' => 'ADMIN_CREATED_SUCCESSFULLY',
            'token' => $user->createToken("API TOKEN")->plainTextToken,
            'user_name' => $user->user_name,
        ], 200);
    }

    public function edit(EditAdminRequest $request, Admin $admin)
    {

        if ($admin->user->update($request->getUserData())) {
            return response()->json([
                'status' => true,
                'message' => 'ADMIN_UPDATED_SUCCESSFULLY',
            ], 200);
        }

        if($request->has('role_id') ){
            $role = Role::find($request->role_id);
            $admin->user->assignRole($role->name);
        }
        AdminLogService::createLog('Admin updated with name: ' . $admin->user->user_name);

        return response()->json([
            'status' => true,
            'message' => 'ADMIN_UPDATE_FAILED',
        ], 200);

    }

    public function login(LoginAdminRequest $request)
    {
        $validated = $request->validated();

        if (!$user = User::checkAdminUserName($validated['user_name'])) {
            return response()->json([
                'status' => false,
                'message' => 'USER_DOES_NOT_EXIST'
            ], 404);
        }
        $permissionArray = $user->getAllPermissions()->pluck('name');
        $webrequestservice = new WebRequestService($request);
        $clientIP = $webrequestservice->getIpAddress();
        $whitelistip = WhitelistIP::where('ip', $clientIP)->first();

        if(!($whitelistip||$user->can('Access from any IP'))){
            return response()->json([
                'status' => false,
                'message' => 'IP_NOT_WHITELISTED'
            ], 401);
        }

        if (!$user->verifyPassword($validated['password'])) {
            return response()->json([
                'status' => false,
                'message' => 'PASSWORD_INCORRECT'
            ], 403);
        }

        if (!$user->admin->status) {
            return response()->json([
                'status' => false,
                'message' => 'ACCOUNT_INACTIVE'
            ], 402);
        }

        if ($user->admin->is_2fa_enabled) {

            if (!$request->otp) {

                return response()->json([
                    'status' => false,
                    'message' => 'OTP_REQUIRED',
                ], 422);
            }

            if (!$user->admin->verifyOTP($validated['otp'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'OTP_INCORRECT'
                ], 403);
            }
        }

        $user->tokens()->delete();

        $webrequestservice = new WebRequestService($request);
        $user->loginHistory()->create(['ip' => $webrequestservice->getIpAddress()]);

        $user->load('roles:name');

        return response()->json([
            'status' => true,
            'message' => 'ADMIN_LOGGED_IN_SUCCESSFULLY',
            'token' => $user->createToken("API TOKEN")->plainTextToken,
            'user_name' => $user->user_name,
            'user_id' => $user->id,
            'admin_id'  => $user->admin->id,
            'is_2fa_enabled' => $user->admin->is_2fa_enabled,
            'roles' => $user->roles->map(fn($item) => Arr::except($item, 'pivot')),
            'permissions' => $permissionArray,
        ], 200);
    }
    public function listAdmins(ListAdminRequest $request)
    {
        return Admin::getAdminsWithUserAndRoles($request->validated())->orderByDesc('id')->paginate(10, ['id', 'user_id', 'status']);
    }

    public function toggleStatus(Admin $admin)
    {
        if ($admin->update(['status' => !$admin->status])) {
            AdminLogService::createLog('Admin ' . $admin->user->user_name . ' status is updated');

            return response()->json([
                'status' => true,
                'message' => 'STATUS_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'STATUS_UPDATE_FAILED'
        ], 400);
    }

    public function createTwoFactorAuth()
    {
        $admin = Auth::user()->admin;

        if (!$admin->is_2fa_enabled) {

            $qrCode = $admin->create2fA();
            AdminLogService::createLog('Admin ' . $admin->user->user_name . ' 2FA is created');

            return response()->json([
                'status' => true,
                'message' => '2FA_CREATED_SUCCESSFULLY',
                'qr_code_url' => $qrCode,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => '2FA_ALREADY_ENABLED',
        ], 403);
    }

    public function disableTwoFactorAuth(DisableTwoFactorAuthRequest $request)
    {
        $validated = $request->validated();

        $user = Auth::user();

        $admin = $user->admin;

        if (!$user->verifyPassword($validated['password'])) {

            return response()->json([
                'status' => false,
                'message' => 'PASSWORD_INCORRECT'
            ], 403);
        }

        $admin->is_2fa_enabled = false;
        $admin->save();
        AdminLogService::createLog('Admin ' . $admin->user->user_name . ' 2FA is disabled');

        return response()->json([
            'status' => true,
            'message' => '2FA_DISABLED_SUCCESSFULLY',
        ]);
    }

    public function firstOTPCheck(OTPCheckRequest $request)
    {
        $validated = $request->validated();

        $admin = Auth::user()->admin;

        if (!$admin->verifyOTP($validated['otp'])) {

            return response()->json([
                'status' => false,
                'message' => 'OTP_INCORRECT'
            ], 403);
        }

        $admin->is_2fa_enabled = true;
        $admin->save();

        return response()->json([
            'status' => true,
            'message' => '2FA_ENABLED_SUCCESSFULLY',
        ]);
    }
}
