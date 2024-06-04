<?php

use App\Constants\WithdrawConstants;
use App\Constants\TransactionConstants;
use App\Constants\UserConstants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(WithdrawConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained(TransactionConstants::TABLE_NAME)->nullOnDelete();
            $table->boolean('is_fa_locked')->default(TransactionConstants::FA_OPEN);
            $table->foreignId('fa_locked_by')->nullable()->constrained(UserConstants::TABLE_NAME)->nullOnDelete();
            $table->boolean('is_risk_locked')->default(TransactionConstants::RISK_OPEN);
            $table->foreignId('risk_locked_by')->nullable()->constrained(UserConstants::TABLE_NAME)->nullOnDelete();
            $table->tinyInteger('risk_action_status')->nullable()->default(TransactionConstants::RISK_ACTION_PENDING);
            $table->foreignId('risk_action_by')->nullable()->constrained(UserConstants::TABLE_NAME)->nullOnDelete();
            $table->boolean('is_first')->default(false);
            $table->text('risk_action_note')->nullable();
            $table->text('payment_remark')->nullable();
            $table->string('reference_no')->nullable();

            $table->timestamps();

            // Adding indexes


            $table->index('is_fa_locked');
            $table->index('is_risk_locked');
            $table->index('risk_action_status');
            $table->index('is_first');
            $table->index('reference_no');
            $table->index('created_at');
            $table->index('updated_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(WithdrawConstants::TABLE_NAME);
    }
};
