<?php

use App\Constants\BankCodeConstants;
use App\Constants\PaymentCategoryConstants;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(BankCodeConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->integer('code')->index();
            $table->string('image')->index();
            $table->foreignId('payment_category_id')->constrained(PaymentCategoryConstants::TABLE_NAME);
            $table->json('public_name')->index();
            $table->boolean("display_for_players")->index();
            $table->boolean("status")->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(BankCodeConstants::TABLE_NAME);
    }
};
