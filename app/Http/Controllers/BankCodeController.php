<?php

namespace App\Http\Controllers;

use App\Constants\BankCodeConstants;
use App\Models\BankCode;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBankCodeRequest;
use App\Http\Requests\UpdateBankCodeRequest;
use App\Services\LogService\AdminLogService;

class BankCodeController extends Controller
{
    public function store(StoreBankCodeRequest $request)
    {
        BankCode::create($request->getBankCodeData());
        AdminLogService::createLog('new Bank Code ' . $request->name . 'is added to the system ');

        return response()->json([
            'status' => true,
            'message' => 'BANK_CODE_CREATED_SUCCESSFULLY'
        ], 200);
    }

    public function getConstants()
    {
        return BankCodeConstants::getCodes();
    }


    public function toggleStatus(BankCode $bank_code)
    {
        if ($bank_code->update(['status' => !$bank_code->status])) {
            AdminLogService::createLog('Bank Code' . $bank_code->code . ' Status Changed');

            return response()->json([
                'status' => true,
                'message' => 'STATUS_TOGGLED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'STATUS_TOGGLE_FAILED'
        ], 400);
    }

    public function toggleDisplayForPlayers(BankCode $bank_code)
    {
        if ($bank_code->update(['display_for_players' => !$bank_code->display_for_players])) {
            AdminLogService::createLog('Bank Code' . $bank_code->code . ' Display For player Changed');
            return response()->json([
                'status' => true,
                'message' => 'DISPLAY_PLAYER_TOGGLED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'DISPLAY_PLAYER_TOGGLE_FAILED'
        ], 400);
    }


    public function update(UpdateBankCodeRequest $request, BankCode $bank_code)
    {
        if ($bank_code->update($request->getBankCodeData())) {
            AdminLogService::createLog('Bank Code ' . $request->name . 'is updated to the system ');

            return response()->json([
                'status' => true,
                'message' => 'BANK_CODE_UPDATED_SUCCESSFULLY'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'BANK_CODE_UPDATE_FAILED'
        ], 400);
    }

    public function index()
    {
        return BankCode::with('paymentCategory:id,name')->orderBy('id', 'desc')->get();
    }

    public function dropDownUser()
    {
        return BankCode::where('display_for_players', true)
                ->where('status', true)->orderByDesc('id')
                ->select(['id', 'code', 'public_name'])
                ->get()
                ->makeHidden('full_image');
        // $bank_codes = BankCode::where('status', true)->orderBy('id', 'desc')->get();
        // return $bank_codes->mapWithKeys(function ($bank_code) {
        //     return [$bank_code->id => $bank_code->code_name];
        // });
    }

    public function dropDownAdmin()
    {
        return BankCode::where('status', true)
                ->orderByDesc('id')
                ->select(['id', 'code', 'public_name'])
                ->get()
                ->makeHidden('full_image');
    }

    // public function bankCodesByPaymentCategory($payment_category_id)
    // {
    //     $bank_codes = BankCode::where('payment_category_id', $payment_category_id)->orderBy('id', 'desc')->get();
    //     return $bank_codes->mapWithKeys(function ($bank_code) {
    //         return [$bank_code->id => $bank_code->code_name];
    //     });
    // }
}
