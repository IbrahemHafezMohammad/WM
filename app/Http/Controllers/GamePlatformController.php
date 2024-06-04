<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGamePlatformRequest;
use App\Http\Requests\ListGamePlatformsRequest;
use App\Http\Requests\UpdateGamePlatformRequest;
use App\Models\GameCategory;
use App\Models\GamePlatform;
use AWS\CRT\HTTP\Request;
use App\Constants\GamePlatformConstants;

class GamePlatformController extends Controller
{

    public function create(CreateGamePlatformRequest $request)
    {
        GamePlatform::create($request->getGamePlatformData());

        return response()->json([
            'status' => true,
            'message' => 'GAME_PLATFORM_CREATED_SUCCESSFULLY'
        ], 200);
    }

    public function update(UpdateGamePlatformRequest $request, GamePlatform $game_platform)
    {
        if ($game_platform->update($request->getGamePlatformData())) {

            return response()->json([
                'status' => true,
                'message' => 'GAME_PLATFORM_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'GAME_PLATFORM_UPDATE_FAILED'
        ], 400);
    }

    public function index(ListGamePlatformsRequest $request)
    {
        return GamePlatform::getGamePlatforms($request->validated())->orderByDesc('id')->paginate(10);
    }

    public function getProviders()
    {
        return GamePlatform::select('id','name')->orderByDesc('id')->get();
    }


    public function dropdown()
    {
        return GamePlatform::orderByDesc('id')->get();
    }

    public function delete(GamePlatform $game_platform)
    {
        if ($game_platform->delete()) {

            return response()->json([
                'status' => true,
                'message' => 'GAME_PLATFORM_DELETED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'GAME_PLATFORM_DELETE_FAILED'
        ], 400);
    }

    public function getPlatformsForCategory(GameCategory $gameCategory)
    {
        if ($gameCategory->childCategories &&  count($gameCategory->childCategories)) {
            return [];
        }
        return GamePlatform::whereRelation('gameItems.gameCategories', 'game_categories.id', $gameCategory->id)->get(['id', 'name', 'icon_image', 'platform_code']);
    }

    public function getGameCodePlatform($provider)
    {
        return $this->getGamePlatformConstant($provider);
    }

    protected function getGamePlatformConstant($provider)
    {
        switch ($provider) {
            case 'aws':
                return GamePlatformConstants::getAWCGameTypes();
                break;
            case 'evo':
                return GamePlatformConstants::getEVOGameTypes();
                break;
            case 'ug':
                return GamePlatformConstants::getUGGameTypes();
                break;
            case 'cmd':
                return GamePlatformConstants::getCMDGameTypes();
                break;
            case 'km':
                return GamePlatformConstants::getKMGameTypes();
                break;
            case 'via':
                return GamePlatformConstants::getVIAGameTypes();
                break;
            case 'saba':
                return GamePlatformConstants::getSABAGameTypes();
                break;
            default:
        }
    }
}
