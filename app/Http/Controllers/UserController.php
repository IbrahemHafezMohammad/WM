<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Constants\GlobalConstants;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\LogService\AdminLogService;
use App\Http\Requests\AssignRoleToUserRequest;
use App\Services\WebService\WebRequestService;
use App\Http\Requests\ResetUserPasswordRequest;

class UserController extends Controller
{

    public function passwordReset(ResetUserPasswordRequest $request, User $user)
    {
        if ($user->update(['password' => $request->validated()['new_password']])) {

            $webrequestservice = new WebRequestService($request);
            AdminLogService::createLog('User ' . $user->user_name . ' password changed');
            return response()->json([
                'status' => true,
                'message' => 'PASSWORD_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PASSWORD_UPDATE_FAILED'
        ], 400);
    }

    public function assignRole(AssignRoleToUserRequest $request, User $user)
    {
        $role = Role::firstWhere('id', $request->validated()['role_id']);

        $user->syncRoles($role);
        AdminLogService::createLog('User ' . $user->user_name . 'Role changed to ' . $role->name);

        return response()->json([
            'status' => true,
            'message' => 'ROLE_ASSIGNED_SUCCESSFULLY'
        ], 200);
    }

    public function getPhoneCodes()
    {
        return GlobalConstants::getPhoneCodesWithImages();
    }

    public function listCountries()
    {
        return GlobalConstants::getCountries();
    }
}
