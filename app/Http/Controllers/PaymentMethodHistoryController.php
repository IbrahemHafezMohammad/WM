<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethodHistory;
use App\Services\LogService\AdminLogService;
use App\Http\Requests\ListPaymentMethodHistoriesRequest;

class PaymentMethodHistoryController extends Controller
{
    public function index(ListPaymentMethodHistoriesRequest $request)
    {
        $query = PaymentMethodHistory::getPaymentBankHistory($request->validated());

        $total_amount = clone $query;
        $withdraw_query = clone $query;
        $deposit_query = clone $query;

        $payment_method_histories = $query->orderByDesc('id')->paginate(10);

        return [
            'payment_method_histories' => $payment_method_histories,
            'total_amount' => $total_amount->sum('amount'),
            'total_withdraw' => $withdraw_query->isWithdraw(true)->successStatus()->sum('amount'),
            'total_deposit' => $deposit_query->isWithdraw(false)->successStatus()->sum('amount'),
        ];
    }

    public function getDailyReport()
    {
        $depositQuery = PaymentMethodHistory::isWithdraw(false)->successStatus();

        $withdrawQuery = PaymentMethodHistory::isWithdraw(true)->successStatus();

        $totalDepositsPerDay = $depositQuery->groupBy('date')->orderBy('date')->get([DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as deposit')])->toArray();

        $totalWithdrawsPerDay = $withdrawQuery->groupBy('date')->orderBy('date')->get([DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as withdraw')])->toArray();

        $totalProfitsPerDay = PaymentMethodHistory::getTotalProfitsPerDay($totalDepositsPerDay, $totalWithdrawsPerDay);

        return [
            'totalDepositsPerDay' => $totalDepositsPerDay,
            'totalWithdrawsPerDay' => $totalWithdrawsPerDay,
            'totalProfitsPerDay' => $totalProfitsPerDay
        ];
    }
}
