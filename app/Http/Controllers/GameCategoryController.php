<?php

namespace App\Http\Controllers;

use App\Models\GameItem;
use App\Models\GameCategory;
use App\Models\GamePlatform;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\DB;
use App\Constants\GameItemConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Constants\GameCategoryConstants;
use App\Constants\GamePlatformConstants;
use App\Services\LogService\AdminLogService;
use App\Services\WebService\WebRequestService;
use App\Services\GameService\GlobalGameService;
use App\Constants\GameItemGameCategoryConstants;
use App\Http\Requests\CreateGameCategoryRequest;
use App\Http\Requests\ListGameCategoriesRequest;
use App\Http\Requests\UpdateGameCategoryRequest;
use App\Http\Requests\ChangeGameItemsOrderRequest;
use App\Http\Requests\ChangeGameCategoriesOrderRequest;

class GameCategoryController extends Controller
{
    public function create(CreateGameCategoryRequest $request)
    {
        GameCategory::create($request->getGameCategoryData());

        $currencies = GlobalConstants::getCurrencies();
        // foreach ($currencies as $currency => $name) {
        //     Cache::forget('game_categories_items_' . $currency);
        // }

        $webService = new WebRequestService($request);
        AdminLogService::createLog('Created New Game Category ' . $request->en);

        return response()->json([
            'status' => true,
            'message' => 'GAME_CATEGORY_CREATED_SUCCESSFULLY'
        ], 200);
    }

    public function update(UpdateGameCategoryRequest $request, GameCategory $game_category)
    {
        if ($game_category->update($request->getGameCategoryData())) {
            $currencies = GlobalConstants::getCurrencies();
            // foreach ($currencies as $currency => $name) {
            // Cache::forget('game_categories_items_' . $currency);
            // }
            $webService = new WebRequestService($request);
            AdminLogService::createLog('Updated Game Category. ID:' . $game_category->id);
            return response()->json([
                'status' => true,
                'message' => 'GAME_CATEGORY_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'GAME_CATEGORY_UPDATE_FAILED'
        ], 400);
    }

    public function index()
    {
        return GameCategory::with('parentCategory:id,name')->orderBy('sort_order')->paginate(10);
    }

    public function getAll()
    {
        return GameCategory::with('parentCategory:id,name')->orderBy('sort_order')->get(['id', 'name', 'parent_category_id', 'status', 'sort_order']);
    }


    public function listProperties()
    {
        return GameCategoryConstants::getPropertiesName();
    }

    public function getGameCategory(GameCategory $game_category)
    {
        return GameCategory::getAllCategoryGames()->where('id', $game_category->id)->get();
    }

    public function toggleStatus(Request $request, GameCategory $game_category)
    {
        if ($game_category->update(['status' => !$game_category->status])) {

            $currencies = GlobalConstants::getCurrencies();
            // foreach ($currencies as $currency => $name) {
            // Cache::forget('game_categories_items_' . $currency);
            // }
            $webService = new WebRequestService($request);
            AdminLogService::createLog('Changed Game (' . $game_category->id . ') status from ' . (!$game_category->status ? "on" : "off")  . ' to ' . ($game_category->status ? "on" : "off"));
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

    public function changeOrder(ChangeGameCategoriesOrderRequest $request)
    {
        if (GameCategory::changeGameCategoriesSortOrder($request->validated()['records'])) {

            $currencies = GlobalConstants::getCurrencies();
            // foreach ($currencies as $currency => $name) {
            // Cache::forget('game_categories_items_' . $currency);
            // }
            $webService = new WebRequestService($request);
            AdminLogService::createLog('Changed Game Categories Order');

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

    public function changeGamesOrder(ChangeGameItemsOrderRequest $request, GameCategory $game_category)
    {
        $game_category->changeGameItemsSortOrder($request->validated()['records']);

        $currencies = GlobalConstants::getCurrencies();
        // foreach ($currencies as $currency => $name) {
        //     Cache::forget('game_categories_items_' . $currency);
        // }

        return response()->json([
            'status' => true,
            'message' => 'SORT_ORDER_UPDATED_SUCCESSFULLY'
        ], 200);
    }

    public function getCategories(Request $request)
    {
        $validated = $request->validate(
            [
                'currency' => ['required', Rule::in(array_keys(GlobalConstants::getCurrencies()))],
                'is_desktop' => ['nullable', 'boolean'],
                'is_mobile' => ['nullable', 'boolean'],
            ]
        );

        $currency = $validated['currency'];

        $query = GameCategory::where(function ($query) use ($currency) {
            $query->whereDoesntHave('parentCategory')
                ->where(function ($query) use ($currency) {
                    $query->whereHas('gameItems', function ($query) use ($currency) {
                        $query->currency($currency)->where('status', GameItemConstants::STATUS_ACTIVE);
                    })
                        ->orWhereHas('childCategories', function ($query) use ($currency) {
                            $query->where('status', true)->whereHas('gameItems', function ($query) use ($currency) {
                                $query->currency($currency)->where('status', GameItemConstants::STATUS_ACTIVE);
                            });
                        });
                });
        })->where('status', true)->orderBy('sort_order');

        if ($validated['is_desktop'] ?? false) {
            $query->where(function ($query) {
                $query->property(GameCategoryConstants::PROPERTY_DESKTOP_SHOW)->orWhere(function ($query) {
                    $query->notProperty(GameCategoryConstants::PROPERTY_DESKTOP_SHOW)->notProperty(GameCategoryConstants::PROPERTY_MOBILE_SHOW);
                });
            });
        }

        if ($validated['is_mobile'] ?? false) {
            $query->where(function ($query) {
                $query->property(GameCategoryConstants::PROPERTY_MOBILE_SHOW)->orWhere(function ($query) {
                    $query->notProperty(GameCategoryConstants::PROPERTY_DESKTOP_SHOW)->notProperty(GameCategoryConstants::PROPERTY_MOBILE_SHOW);
                });
            });
        }

        return $query->get();
    }

    // ===========================
    // new listing functions

    // for getting child categories
    public function getChildCategories($categoryId)
    {

        return DB::table('game_platforms')
            ->select('game_platforms.name', 'game_platforms.id as platform_id', 'game_platforms.icon_image')
            ->join('game_items', 'game_items.game_platform_id', 'game_platforms.id')
            ->join('game_item_game_category', 'game_items.id', 'game_item_game_category.game_item_id')
            ->where('game_item_game_category.game_category_id', $categoryId)
            ->distinct()
            ->get();
    }

    // for fetching gameItems
    public function getGameItems($platformId)
    {
        $platformId = (int)$platformId;

        $query  = DB::table('game_items');

        if ((int) $platformId != 0) {
            $query  = $query->where('game_platform_id', $platformId);
        }

        return $query  = $query->paginate(15);
    }

    // for searching 
    public function getGamePlatforms($platformId, $searchParam)
    {
        $query  =  DB::table('game_items');

        $platformId = (int)$platformId;

        if ($platformId != null || (int) $platformId != 0) {
            $query  = $query->where('game_platform_id', $platformId);
        }
        $query  = $query->where(DB::raw('LOWER(name)'), 'like', '%' . strtolower($searchParam) . '%');

        return $query->paginate(15);
    }

    public function listGameCategories(Request $request)
    {

        // Default pagination value. | 30 for desktop  
        $numberOfItems = 15;

        if ($request->is_desktop) {
            $numberOfItems = 27;
        }

        $validated = $request->validate(
            [
                'currency' => ['required', Rule::in(array_keys(GlobalConstants::getCurrencies()))],
                'category_id' => ['required', 'integer', Rule::exists(GameCategoryConstants::TABLE_NAME, 'id')],
                'is_desktop' => ['nullable', 'boolean'],
                'is_mobile' => ['nullable', 'boolean'],
                'game_name' => ['nullable', 'string'],
                'platform_ids' => ['sometimes', 'array'],
                'platform_ids.*' => ['integer', Rule::exists(GamePlatformConstants::TABLE_NAME, 'id')],
            ]
        );

        $category = GameCategory::getGameCategoriesWithGames(
            $validated['currency'],
            $validated['is_desktop'] ?? false,
            $validated['is_mobile'] ?? false,
            $validated['game_name'] ?? null,
            $validated['platform_ids'] ?? null,
        )
            ->where('status', true)
            ->where('id', $validated['category_id'])
            ->where('parent_category_id', null)
            ->orderBy('sort_order')
            ->first();

        $gameItemsQuery = GameItem::whereHas('gameCategories', function ($query) use ($validated) {
            $query->where(GameCategoryConstants::TABLE_NAME . '.id', $validated['category_id']);
        })
            ->where('status', GameItemConstants::STATUS_ACTIVE)
            ->currency($validated['currency']);

        if (isset($validated['game_name'])) {
            $gameItemsQuery->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($validated['game_name']) . '%']);
        }
        if (isset($validated['platform_ids'])) {
            $gameItemsQuery->whereIn('game_platform_id', $validated['platform_ids']);
        }

        $gameItemsQuery->join(GameItemGameCategoryConstants::TABLE_NAME, GameItemConstants::TABLE_NAME . '.id', '=', GameItemGameCategoryConstants::TABLE_NAME . '.game_item_id')
            ->where(GameItemGameCategoryConstants::TABLE_NAME . '.game_category_id', $validated['category_id'])
            ->orderBy(GameItemGameCategoryConstants::TABLE_NAME . '.game_item_sort_order');


        $paginatedGameItems = $gameItemsQuery->with('gamePlatform')->paginate($numberOfItems);

        return response()->json([
            'category' => $category,
            'gameItems' => $paginatedGameItems
        ]);
    }

    public function delete(Request $request, GameCategory $game_category)
    {
        if ($game_category->delete()) {

            $currencies = GlobalConstants::getCurrencies();
            // foreach ($currencies as $currency => $name) {
            // Cache::forget('game_categories_items_' . $currency);
            // }
            $webService = new WebRequestService($request);
            AdminLogService::createLog('Game Category Deleted. ID: ' . $game_category->id);

            return response()->json([
                'status' => true,
                'message' => 'GAME_CATEGORY_DELETED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'GAME_CATEGORY_DELETE_FAILED'
        ], 400);
    }
}
