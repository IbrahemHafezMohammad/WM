<?php

use App\Constants\UserConstants;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Schema;
use App\Constants\TransactionConstants;
use Illuminate\Database\Schema\Blueprint;
use App\Constants\UserPaymentMethodConstants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(TransactionConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION);
            $table->integer('currency');
            $table->tinyInteger('status')->default(TransactionConstants::STATUS_PENDING);
            $table->foreignId('processing_by')->nullable()->constrained(UserConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('action_by')->nullable()->constrained(UserConstants::TABLE_NAME)->nullOnDelete();
            $table->boolean('isWithdraw')->default(false);
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_payment_method_id')->nullable()->constrained(UserPaymentMethodConstants::TABLE_NAME)->nullOnDelete();
            $table->text('attachment_url')->nullable();
            $table->text('remark')->nullable();
            $table->text('customer_message')->nullable();
            $table->boolean('manual_approval')->default(false);
            $table->dateTime('action_time')->nullable();

            $table->timestamps();

            // Adding indexes
            $table->index('amount');
            $table->index('currency');
            $table->index('status');
            $table->index('isWithdraw');
            $table->index('attachment_url');
            $table->index('remark');
            $table->index('customer_message');
            $table->index('manual_approval');
            $table->index('action_time');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(TransactionConstants::TABLE_NAME);
    }
};
