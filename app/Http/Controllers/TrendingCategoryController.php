<?php

namespace App\Http\Controllers;

use App\Models\TrendingCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrendingCategoryController extends Controller
{
    const DESKTOP = 1;
    const MOBILE = 0;

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'game_category_id' => 'required|exists:game_categories,id',
            'status' => 'required|boolean',
            'sort_order' => 'integer|nullable',
            'active_image' => 'string|nullable',
            'inactive_image' => 'string|nullable',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        try {

            $validatedData = $validator->validated();
            $existingTrendingCategory = TrendingCategory::where('game_category_id', $validatedData['game_category_id'])->where('status',$validatedData['status'])->first();
    
            if ($existingTrendingCategory) {
                return response()->json(['message' => 'Duplicate category: A trending category with this game category ID already exists.'], 409); // Conflict
            }

            $trendingCategory = TrendingCategory::create($validatedData);
            return response()->json($trendingCategory, 201); // Created

        } catch (QueryException $e) {

            return response()->json(['message' => 'Database error: ' . $e->getMessage()], 500);

        } catch (\Exception $e) {

            return response()->json(['message' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function index()
    {
        return response()->json(TrendingCategory::all());
    }

    public function getAlltrendingCategory()
    {
        return response()->json(TrendingCategory::select('id','game_category_id','sort_order','active_image','inactive_image')->where('status',TrendingCategoryController::DESKTOP)->orderBy('sort_order')->limit(4)->get());
    }


    public function edit($id)
    {
        $trendingCategory = TrendingCategory::find($id);

        if (!$trendingCategory) {

            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json($trendingCategory);
    }

    public function update(Request $request, $id)
    {
        $trendingCategory = TrendingCategory::find($id);

        if (!$trendingCategory) {

            return response()->json(['message' => 'Not Found'], 404);
        }

        $trendingCategory->update($request->all());

        return response()->json($trendingCategory); // Return the updated record
    }

    public function destroy($id)
    {
        $trendingCategory = TrendingCategory::find($id);

        if (!$trendingCategory) {

            return response()->json(['message' => 'Not Found'], 404);
        }
        $trendingCategory->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
