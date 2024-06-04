<?php

use App\Constants\GlobalConstants;
use App\Constants\BankCodeConstants;
use Illuminate\Support\Facades\Schema;
use App\Constants\PaymentMethodConstants;
use Illuminate\Database\Schema\Blueprint;
use App\Constants\PaymentCategoryConstants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(PaymentMethodConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_code_id')->constrained(BankCodeConstants::TABLE_NAME);
            $table->foreignId('payment_category_id')->constrained(PaymentCategoryConstants::TABLE_NAME);
            $table->string('account_name');
            $table->string('account_number')->nullable();
            $table->json('public_name');
            $table->string('bank_city')->nullable();
            $table->string('bank_branch')->nullable();
            $table->boolean('allow_deposit')->default(true);
            $table->boolean('allow_withdraw')->default(true);
            $table->boolean('under_maintenance')->default(false);
            $table->string('api_key')->nullable();
            $table->string('callback_key')->nullable();
            $table->string('api_url')->nullable();
            $table->text('remark')->nullable();
            $table->decimal('balance', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION);
            $table->decimal('max_daily_amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->decimal('max_total_amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->decimal('min_deposit_amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->decimal('max_deposit_amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->decimal('min_withdraw_amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->decimal('max_withdraw_amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->integer('currency');
            $table->integer('payment_code');
            $table->timestamps();


            // Adding indexes
            $table->index('account_name');
            $table->index('public_name');
            $table->index('allow_deposit');
            $table->index('allow_withdraw');
            $table->index('under_maintenance');
            $table->index('api_key');
            $table->index('callback_key');
            $table->index('api_url');
            $table->index('remark');
            $table->index('balance');
            $table->index('max_daily_amount');
            $table->index('max_total_amount');
            $table->index('min_deposit_amount');
            $table->index('max_deposit_amount');
            $table->index('min_withdraw_amount');
            $table->index('max_withdraw_amount');
            $table->index('currency');
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('payment_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(PaymentMethodConstants::TABLE_NAME);
    }
};
