<?php

use App\Constants\DepositConstants;
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
        Schema::create(DepositConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained(TransactionConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('fa_locked_by')->nullable()->constrained(UserConstants::TABLE_NAME)->nullOnDelete();
            $table->boolean('is_fa_locked')->default(TransactionConstants::FA_OPEN);
            $table->string('deposit_transaction_no')->unique()->nullable();
            $table->boolean('is_first')->default(false);
            $table->string('reference_no')->nullable();
            $table->text('payment_remark')->nullable();
            $table->json('payment_info')->nullable();
            $table->text('payment_link')->nullable();
            $table->timestamps();

            // Adding indexes
            $table->index('is_fa_locked');
            $table->index('is_first');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(DepositConstants::TABLE_NAME,);
    }
};
