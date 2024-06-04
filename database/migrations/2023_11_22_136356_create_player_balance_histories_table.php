<?php

use App\Constants\UserConstants;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Schema;
use App\Constants\TransactionConstants;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Constants\PlayerBalanceHistoryConstants;
use App\Constants\GameTransactionHistoryConstants;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(PlayerBalanceHistoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION);
            $table->decimal('previous_balance', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION);
            $table->decimal('new_balance', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->tinyInteger('currency');
            $table->boolean('is_deduction');
            $table->tinyInteger('status')->default(PlayerBalanceHistoryConstants::STATUS_PENDING);
            $table->foreignId('transaction_id')->nullable()->constrained(TransactionConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('game_transaction_history_id')->nullable()->constrained(GameTransactionHistoryConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('action_by')->nullable()->constrained(UserConstants::TABLE_NAME)->nullOnDelete();
            $table->text('remark')->nullable();
            $table->timestamps();

            // Adding indexes
            $table->index('amount');
            $table->index('previous_balance');
            $table->index('new_balance');
            $table->index('currency');
            $table->index('is_deduction');
            $table->index('status');
            $table->index('remark');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(PlayerBalanceHistoryConstants::TABLE_NAME);
    }
};
