<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Http\Requests\StoreSettingRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Requests\ListSettingRequest;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListSettingRequest $request)
    {
        return Setting::getSettings($request->validated())->paginate(5);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSettingRequest $request)
    {
        Setting::create($request->getSettingData());

        return response()->json([
          'status' => true,
          'message' => 'SETTING_CREATED_SUCCESSFULLY',
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSettingRequest $request , Setting $setting)
    {
        if ($setting->update($request->getSettingData())){
            return response()->json([
                'status' => true,
                'message' => 'SETTING_UPDATED_SUCCESSFULLY',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'SETTING_UPDATE_FAILED',
        ], 400);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting)
    {
        $setting->delete();
        return response()->json([
            'status' => true,
            'message' => 'SETTING_DELETED_SUCCESSFULLY',
        ], 200);
    }
}
