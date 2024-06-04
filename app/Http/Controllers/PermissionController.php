<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;

class PermissionController extends Controller
{
    public function store(StorePermissionRequest $request)
    {
        Permission::create($request->getPermissionData());

        return response()->json([
            'status' => true,
            'message' => 'PERMISSION_CREATED_SUCCESSFULLY',
        ], 200);
    }

    public function index()
    {
        if (!Auth::user()->can('View Permission')) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_DENIED'
            ], 403);
        }

        return Permission::all();
    }

    public function update(UpdatePermissionRequest $request, string $id)
    {
        $permission = Permission::findById($id);

        if (!$permission) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_NOT_FOUND'
            ], 404);
        }

        if ($permission->update($request->getPermissionData())) {

            return response()->json([
                'status' => true,
                'message' => 'PERMISSION_UPDATED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PERMISSION_UPDATE_FAILED',
        ], 200);
    }

    public function delete(string $id)
    {
        $permission = Permission::findById($id);

        if (!$permission) {
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_NOT_FOUND'
            ], 404);
        }

        if ($permission->delete()) {

            return response()->json([
                'status' => true,
                'message' => 'PERMISSION_DELETED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PERMISSION_DELETE_FAILED'
        ], 400);
    }
}