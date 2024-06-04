<?php

namespace App\Http\Controllers;

use App\Models\GameItem;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Log;
use App\Constants\GameItemConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\ListGameItemsRequest;
use App\Http\Requests\CreateGameItemRequest;
use App\Http\Requests\UpdateGameItemRequest;
use App\Services\LogService\AdminLogService;
use App\Services\WebService\WebRequestService;
use App\Http\Requests\ChangeGameItemsOrderRequest;
use App\Http\Requests\ChangeGameItemStatusRequest;

class GameItemController extends Controller
{
    public function create(CreateGameItemRequest $request)
    {
        $validated = $request->validated();

        $game_item = GameItem::create($request->getGameItemData($validated));

        $categories_ids = array_column($validated['game_category_ids'], 'id');

        $game_item->gameCategories()->syncWithPivotValues($categories_ids, ['game_item_sort_order' => 0]);
        $webService = new WebRequestService($request);
        AdminLogService::createLog('Created New Game Item ' . $validated['en'], Auth::user()->id, $webService->getIpAddress());
        $currencies = GlobalConstants::getCurrencies();
        // foreach ($currencies as $currency => $name) {
        //     Cache::forget('game_categories_items_' . $currency);
        // }

        return response()->json([
            'status' => true,
            'message' => 'GAME_ITEM_CREATED_SUCCESSFULLY'
        ], 200);
    }

    public function update(UpdateGameItemRequest $request, GameItem $game_item)
    {
        $validated = $request->validated();

        if ($game_item->update($request->getGameItemData($validated))) {

            $categories_ids = array_column($validated['game_category_ids'], 'id');

            $game_item->updateGameCategories($categories_ids);

            $currencies = GlobalConstants::getCurrencies();
            // foreach ($currencies as $currency => $name) {
            //     Cache::forget('game_categories_items_' . $currency);
            // }

            return response()->json([
                'status' => true,
                'message' => 'GAME_ITEM_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'GAME_ITEM_UPDATE_FAILED'
        ], 400);
    }

    public function index(ListGameItemsRequest $request)
    {
        return GameItem::getGameItems($request->validated())->paginate(10);
    }

    public function listProperties()
    {
        return GameItemConstants::getPropertiesName();
    }

    public function changeStatus(ChangeGameItemStatusRequest $request, GameItem $game_item)
    {
        $validated = $request->validated();

        if ($game_item->update(['status' => $validated['status']])) {

            $currencies = GlobalConstants::getCurrencies();
            // foreach ($currencies as $currency => $name) {
            //     Cache::forget('game_categories_items_' . $currency);
            // }

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

    // if deleted it'll affect the game transaction history (game related records wil be detached from the game transaction history table (game_item_id become null))

    // public function delete(GameItem $game_item)
    // {
    //     if ($game_item->deleteAllImages()) {

    //         $game_item->delete();

    //          $currencies = GlobalConstants::getCurrencies();
    // foreach($currencies as $currency) {
    //     Cache::forget('game_categories_items_' . $currency);
    // }

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'GAME_ITEM_DELETED_SUCCESSFULLY'
    //         ], 200);
    //     }

    //     return response()->json([
    //         'status' => false,
    //         'message' => 'GAME_ITEM_DELETE_FAILED'
    //     ], 400);

    // }
}
