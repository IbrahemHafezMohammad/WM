<?php

use App\Constants\AdminConstants;
use App\Constants\GlobalConstants;
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
        Schema::create('win_loss_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchased_by')->nullable()->constrained(AdminConstants::TABLE_NAME)->nullOnDelete();
            $table->tinyInteger('currency')->default(GlobalConstants::CURRENCY_USD);
            $table->double('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('win_loss_purchases');
    }
};
