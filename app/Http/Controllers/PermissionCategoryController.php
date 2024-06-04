<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermissionCategoryRequest;
use App\Models\PermissionCategory;

class PermissionCategoryController extends Controller
{
    public function store(StorePermissionCategoryRequest $request)
    {
        PermissionCategory::create($request->getPermissionCategoryData());

        return response()->json([
            'status' => true,
            'message' => 'PERMISSION_CATEGORY_CREATED_SUCCESSFULLY',
        ], 200);
    }
}