<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\StorePromotionRequest;
use App\Services\LogService\AdminLogService;
use App\Http\Requests\UpdatePromotionRequest;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function store(StorePromotionRequest $request)
    {
        $validated = $request->validated();

        $promotion = Promotion::create($request->getPromotionData());

        $promotion->promotionCategories()->syncWithPivotValues($validated['promotion_category_ids'], ['promotion_sort_order' => 0]);

        AdminLogService::createLog('New Promotion '.$promotion->title.' Created');

        return response()->json([
            'status' => true,
            'message' => 'PROMOTION_CREATED_SUCCESSFULLY',
        ], 200);
    }

    public function delete(Promotion $promotion)
    {
        if ($promotion->deleteAllImages()) {

            if ($promotion->delete()) {

                return response()->json([
                    'status' => true,
                    'message' => 'PROMOTION_DELETED_SUCCESSFULLY',
                ], 200);
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'PROMOTION_DELETE_FAILED',
        ], 400);

    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion)
    {
        $validated = $request->validated();

        if ($promotion->update($request->getPromotionData())) {


            $promotion->updatePromotionCategories($validated['promotion_category_ids']);

            AdminLogService::createLog('Promotion '.$promotion->title.' is updated');

            return response()->json([
                'status' => true,
                'message' => 'PROMOTION_UPDATED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PROMOTION_UPDATE_FAILED',
        ], 400);
    }

    public function index(Request $request)
    {
        $promotions = Promotion::with(['promotionCategories', 'actionBy:id,user_name'])->orderByDesc('id');

        if ($request->has("search")) {
            $promotions->where('title', 'like', "%$request->search%");
        }

        if(!Auth::user()->hasPermissionTo('View Promotions')){
            return response()->json([
                'status' => false,
                'message' => 'PERMISSION_DENIED',
            ],403);
        }

        return $promotions->paginate(5);
    }

    public function toggleStatus(Promotion $promotion)
    {
        if ($promotion->toggleStatus(Auth::user()->id)) {
            AdminLogService::createLog('Promotion status is changed to '.' :'.$promotion->status);
            return response()->json([
                'status' => true,
                'message' => 'PROMOTION_STATUS_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PROMOTION_STATUS_UPDATE_FAILED'
        ], 400);
    }
}
