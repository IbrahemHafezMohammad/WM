<?php

use App\Constants\AdminConstants;
use App\Constants\UserConstants;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Schema;
use App\Constants\TransactionConstants;
use App\Constants\PaymentMethodConstants;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Constants\PaymentMethodHistoryConstants;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(PaymentMethodHistoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION);
            $table->boolean('isWithdraw');
            $table->foreignId('changed_by')->nullable()->constrained(AdminConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained(TransactionConstants::TABLE_NAME)->nullOnDelete();
            $table->text('remark')->nullable();
            $table->tinyInteger('status')->default(PaymentMethodHistoryConstants::STATUS_PENDING);
            $table->decimal('previous_balance', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION);
            $table->decimal('new_balance', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION);
            $table->foreignId('payment_method_id')->constrained(PaymentMethodConstants::TABLE_NAME)->cascadeOnDelete();
            $table->timestamps();

            // Adding indexes
            $table->index('amount');
            $table->index('isWithdraw');
            $table->index('remark');
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(PaymentMethodHistoryConstants::TABLE_NAME);
    }
};
