<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\PromotionCategory;
use App\Constants\GlobalConstants;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Services\LogService\AdminLogService;
use App\Services\WebService\WebRequestService;
use App\Http\Requests\ChangePromotionsOrderRequest;
use App\Http\Requests\CreatePromotionCategoryRequest;
use App\Http\Requests\ListPromotionCategoriesRequest;
use App\Http\Requests\UpdatePromotionCategoryRequest;
use App\Http\Requests\ChangePromotionCategoriesOrderRequest;

class PromotionCategoryController extends Controller
{
    public function create(CreatePromotionCategoryRequest $request)
    {
        PromotionCategory::create($request->getPromotionCategoryData());

        $webService = new WebRequestService($request);
        AdminLogService::createLog('Created New Promotion Category ' . $request->en);

        return response()->json([
            'status' => true,
            'message' => 'PROMOTION_CATEGORY_CREATED_SUCCESSFULLY'
        ], 200);
    }

    public function update(UpdatePromotionCategoryRequest $request, PromotionCategory $promotion_category)
    {
        if ($promotion_category->update($request->getPromotionCategoryData())) {
            $webService = new WebRequestService($request);
            AdminLogService::createLog('Updated Promotion Category. ID:' . $promotion_category->id);
            return response()->json([
                'status' => true,
                'message' => 'PROMOTION_CATEGORY_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PROMOTION_CATEGORY_UPDATE_FAILED'
        ], 400);

    }

    public function index(ListPromotionCategoriesRequest $request)
    {
        return PromotionCategory::getPromotionCategories($request->validated())->orderByDesc('sort_order')->paginate(5);
    }

    public function toggleStatus(Request $request, PromotionCategory $promotion_category)
    {
        if ($promotion_category->update(['is_active' => !$promotion_category->is_active])) {

            $webService = new WebRequestService($request);
            AdminLogService::createLog('Changed Promotion Category (' . $promotion_category->id . ') is_active from ' . (!$promotion_category->is_active ? "on" : "off") . ' to ' . ($promotion_category->is_active ? "on" : "off"));
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

    public function changeOrder(ChangePromotionCategoriesOrderRequest $request)
    {
        if (PromotionCategory::changePromotionCategoriesSortOrder($request->validated()['records'])) {

            $webService = new WebRequestService($request);
            AdminLogService::createLog('Changed Promotion Categories Order');

            return response()->json([
                'status' => true,
                'message' => 'SORT_ORDER_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'SORT_ORDER_UPDATE_FAILED'
        ], 400);
    }

    public function changePromotionOrder(ChangePromotionsOrderRequest $request, PromotionCategory $promotion_category)
    {
        $promotion_category->changePromotionsSortOrder($request->validated()['records']);


        return response()->json([
            'status' => true,
            'message' => 'SORT_ORDER_UPDATED_SUCCESSFULLY'
        ], 200);
    }

    public function listPromotionCategories(Request $request)
    {
        $validated = $request->validate([
            'country' => ['required','integer', Rule::in(array_keys(GlobalConstants::getCountries()))],
        ]);

        return PromotionCategory::getWithPromotions($validated['country'])->orderBy('sort_order')->get(['id', 'name', 'sort_order', 'icon_image', 'icon_image_desktop']);
    }

    public function delete(Request $request, PromotionCategory $promotion_category)
    {
        if ($promotion_category->delete()) {

            $webService = new WebRequestService($request);
            AdminLogService::createLog('Promotion Category Deleted. ID: ' . $promotion_category->id);

            return response()->json([
                'status' => true,
                'message' => 'PROMOTION_CATEGORY_DELETED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PROMOTION_CATEGORY_DELETE_FAILED'
        ], 400);
    }
}
