<?php

use App\Constants\PromotionConstants;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Constants\PromotionCategoryConstants;
use Illuminate\Database\Migrations\Migration;
use App\Constants\PromotionPromotionCategoryConstants;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(PromotionPromotionCategoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained(PromotionConstants::TABLE_NAME)->cascadeOnDelete();
            $table->foreignId('promotion_category_id')->constrained(PromotionCategoryConstants::TABLE_NAME)->cascadeOnDelete();
            $table->integer('promotion_sort_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(PromotionPromotionCategoryConstants::TABLE_NAME);
    }
};
