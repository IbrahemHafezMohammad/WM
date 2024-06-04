<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\LogService\AdminLogService;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();

        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions($validated['array_of_permissions']);
        AdminLogService::createLog( 'New Role created with name: ' . $role->name);
        return response()->json([
            'status' => true,
            'message' => 'ROLE_CREATED_SUCCESSFULLY',
        ], 200);
    }

    public function index()
    {
        return Role::with('permissions:name')->orderByDesc('id')->get();
    }

    public function update(UpdateRoleRequest $request, string $id)
    {
        $role = Role::findById($id);
        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'ROLE_NOT_FOUND'
            ], 404);
        }

        $validated = $request->validated();

        if ($role->update(['name' => $validated['name']]) && $role->syncPermissions($validated['array_of_permissions'])) {
            AdminLogService::createLog( 'Role updated with name: ' . $role->name);

            return response()->json([
                'status' => true,
                'message' => 'ROLE_UPDATED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'ROLE_UPDATE_FAILED',
        ], 400);
    }

    public function delete(string $id)
    {
        $role = Role::findById($id);

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'ROLE_NOT_FOUND'
            ], 404);
        }

        if ($role->delete()) {
            AdminLogService::createLog( 'Role deleted with name: ' . $role->name);
            return response()->json([
                'status' => true,
                'message' => 'ROLE_DELETED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'ROLE_DELETE_FAILED',
        ], 400);
    }
}
