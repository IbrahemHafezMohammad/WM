<?php

namespace App\Http\Controllers;

use App\Models\PlayerRating;
use App\Http\Requests\StorePlayerRatingRequest;
use App\Http\Requests\UpdatePlayerRatingRequest;

class PlayerRatingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PlayerRating::getPlayerRatingData()->paginate(5);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlayerRatingRequest $request)
    {
        PlayerRating::create($request->getPlayerRatingData());

        return response()->json([
            'status' => true,
            'message' => 'PLAYER_RATING_CREATED_SUCCESSFULLY',
          ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlayerRatingRequest $request, PlayerRating $playerRating)
    {
        if ($playerRating->update($request->getPlayerRatingData())){
            return response()->json([
                'status' => true,
                'message' => 'PLAYER_RATING_UPDATED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'PLAYER_RATING_UPDATE_FAILED',
        ], 400);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlayerRating $playerRating)
    {
        $playerRating->delete();
        return response()->json([
            'status' => true,
            'message' => 'PLAYER_RATING_DELETED_SUCCESSFULLY',
        ], 200);
    }
}
