<?php

use App\Constants\UserConstants;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Constants\PromotionCategoryConstants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(PromotionCategoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order');
            $table->text('icon_image')->nullable();
            $table->text('icon_image_desktop')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained(UserConstants::TABLE_NAME)->nullOnDelete();
            $table->timestamps();

            // Adding indexes
            $table->index('name');
            $table->index('is_active');
            $table->index('sort_order');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(PromotionCategoryConstants::TABLE_NAME);
    }
};
