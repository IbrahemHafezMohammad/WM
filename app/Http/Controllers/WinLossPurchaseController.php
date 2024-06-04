<?php

namespace App\Http\Controllers;

use App\Models\WinLossPurchase;
use App\Services\LogService\AdminLogService;
use App\Http\Requests\StoreWinLossPurchase;
use App\Http\Requests\UpdateWinLossPurchase;
class WinLossPurchaseController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => true,
            'win_loss_purchase_data' => WinLossPurchase::with('purchaser.user')->latest()->paginate(5),
            'sum_current_month' => WinLossPurchase::whereMonth('created_at', now()->month)->sum('amount')
        ]);
    }

    public function store(StoreWinLossPurchase $request)
    {
        $purchase = WinLossPurchase::create($request->getWinLossData());
        AdminLogService::createLog("New Winloss Purchase added. ID: " . $purchase->id);
        return response()->json([
            'status' => true,
            'message' => 'WIN_LOSS_PURCHASE_CREATED_SUCCESSFULLY',
        ], 200);
    }

    public function update(UpdateWinLossPurchase $request, WinLossPurchase $winLossPurchase)
    {
        AdminLogService::createLog("Winloss Purchase ID: ".$winLossPurchase->id . " changed amount from ". $winLossPurchase->amount . " to ". $request['amount']);
        $winLossPurchase->update($request->getWinLossData());
        return response()->json([
            'status' => true,
            'message' => 'WIN_LOSS_PURCHASE_UPDATED_SUCCESSFULLY',
        ], 200);
    }

    public function destroy(WinLossPurchase $winLossPurchase)
    {
        AdminLogService::createLog("Winloss Purchase ID: " .$winLossPurchase->id . " Deleted");
        $winLossPurchase->delete();
        return response()->json([
            'status' => true,
            'message' => 'WIN_LOSS_PURCHASE_DELETED_SUCCESSFULLY',
        ], 200);
    }
}
