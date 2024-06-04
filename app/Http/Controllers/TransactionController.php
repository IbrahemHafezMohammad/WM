<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Constants\TransactionConstants;
use App\Http\Requests\TransactionMarkRequest;
use App\Http\Requests\ListTransactionsRequest;
use App\Http\Requests\ViewTransactionForAdminRequest;
use App\Http\Requests\ListTransactionsForPlayerRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function listTransactions(ListTransactionsRequest $request)
    {
        $query = Transaction::getTransactionsWithRelations($request->validated());

        $total_amount_query = clone $query;

        $total_amount = $total_amount_query->select('currency', DB::raw('SUM(amount) as amount'))->groupBy('currency')->get();

        $total_base_amount = $total_amount->sum('amount');

        return [
            'transactions' => $query->orderByDesc('id')->paginate($request->validated()['per_page'] ?? 10),
            'total_amount' => $total_amount,
            'total_base_amount' => $total_base_amount,
            'base_currency' => GlobalConstants::getCurrency(GlobalConstants::CURRENCY_USD),
            'pending_deposit_count' => Transaction::depositPendingTransactions()->count(),
            'pending_withdraw_count' => Transaction::withdrawPendingTransactions()->count(),
        ];
    }

    public function getPendingTransactionsCount()
    {
        return [
            'pending_deposit_count' => Transaction::depositPendingTransactions()->count(),
            'pending_withdraw_count' => Transaction::withdrawPendingTransactions()->count(),
        ];
    }

    public function listPlayerTransactions(ListTransactionsForPlayerRequest $request)
    {
        return Transaction::getTransactionsForPlayer(Auth::user()->player->id, $request->validated())->orderByDesc('id')->paginate(10);
    }

    public function adminView(ViewTransactionForAdminRequest $request, Transaction $transaction)
    {
        return $transaction->transactionLoadForAdminView();
    }

    public function playerView(Transaction $transaction)
    {
        $transaction = $transaction->transactionLoadForPlayerView()->only([
            'id',
            'player_id',
            'user_payment_method_id',
            'customer_message',
            'currency',
            'amount',
            'status',
            'status_name',
            'created_at',
            'paymentMethod',
            'userPaymentMethod',
            'isDepositTransaction'
        ]);

        if (Auth::user()->player->id !== $transaction['player_id']) {
            return response()->json([
                'status' => false,
                'message' => 'YOU_DONT_HAVE_PERMISSION'
            ], 403);
        }

        return $transaction;
    }

    public function getStatuses()
    {
        return TransactionConstants::getStatuses();
    }

    public function markTransactionStatus(TransactionMarkRequest $request, Transaction $transaction)
    {
        $in_process = $request->validated()['in_process'];

        if ($in_process) {

            if ($transaction->update(['status' => TransactionConstants::STATUS_PROCESSING])) {

                return response()->json([
                    'status' => true,
                    'message' => 'TRANSACTION_STATUS_UPDATED_SUCCESSFULLY'
                ], 200);
            }
        } else {

            if ($transaction->update(['status' => TransactionConstants::STATUS_PENDING])) {

                return response()->json([
                    'status' => true,
                    'message' => 'TRANSACTION_STATUS_UPDATED_SUCCESSFULLY'
                ], 200);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'TRANSACTION_STATUS_UPDATE_FAILED'
        ], 400);
    }


    public function getDailyReport()
    {
        $depositQuery = Transaction::isWithdraw(false)->transactionStatus(TransactionConstants::STATUS_APPROVED);

        $withdrawQuery = Transaction::isWithdraw(true)->transactionStatus(TransactionConstants::STATUS_APPROVED);

        $deposits = $depositQuery->get();
        $withdraws = $withdrawQuery->get();

        $totalDepositsPerDay = $deposits->groupBy(function ($deposit) {
            return $deposit->created_at->format('Y-m-d');
        })->map(function ($group) {
            return $group->sum('base_amount');
        });

        $totalWithdrawsPerDay = $withdraws->groupBy(function ($withdraw) {
            return $withdraw->created_at->format('Y-m-d');
        })->map(function ($group) {
            return $group->sum('base_amount');
        });

        $totalProfitsPerDay = Transaction::getTotalProfitsPerDay($totalDepositsPerDay, $totalWithdrawsPerDay);

        return [
            'totalDepositsPerDay' => $totalDepositsPerDay,
            'totalWithdrawsPerDay' => $totalWithdrawsPerDay,
            'totalProfitsPerDay' => $totalProfitsPerDay
        ];
    }
}
