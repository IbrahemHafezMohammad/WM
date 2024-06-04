<?php

use App\Constants\GameCategoryConstants;
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
        Schema::create(GameCategoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->boolean('status')->default(GameCategoryConstants::IS_ACTIVE);
            $table->boolean('is_lobby')->default(false);
            $table->integer('sort_order');
            $table->text('icon_image')->nullable();
            $table->text('icon_active')->nullable();
            $table->text('icon_image_desktop')->nullable();
            $table->text('icon_active_desktop')->nullable();
            $table->foreignId('parent_category_id')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained(UserConstants::TABLE_NAME)->nullOnDelete();
            $table->timestamps();

            // Adding indexes
            $table->index('name');
            $table->index('status');
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
        Schema::dropIfExists(GameCategoryConstants::TABLE_NAME);
    }
};
