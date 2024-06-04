<?php

use App\Constants\UserConstants;
use App\Constants\GlobalConstants;
use App\Constants\BankCodeConstants;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Constants\PaymentCategoryConstants;
use App\Constants\UserPaymentMethodConstants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(UserPaymentMethodConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_code_id')->constrained(BankCodeConstants::TABLE_NAME);
            $table->foreignId('payment_category_id')->constrained(PaymentCategoryConstants::TABLE_NAME);
            $table->foreignId('user_id')->constrained(UserConstants::TABLE_NAME)->cascadeOnDelete();
            $table->string('account_name');
            $table->string('account_number');
            $table->string('bank_city')->nullable();
            $table->string('bank_branch')->nullable();
            $table->boolean('is_active')->default(false);
            $table->text('remark')->nullable();
            $table->integer('currency');
            $table->timestamps();

            // Adding indexes
            $table->index('account_name');
            $table->index('account_number');
            $table->index('bank_city');
            $table->index('bank_branch');
            $table->index('remark');
            $table->index('currency');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(UserPaymentMethodConstants::TABLE_NAME);
    }
};
