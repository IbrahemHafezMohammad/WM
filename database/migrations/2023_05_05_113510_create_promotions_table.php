<?php

use App\Constants\PromotionConstants;
use App\Constants\UserConstants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(PromotionConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('status')->default(!PromotionConstants::STATUS_VISIBLE);
            $table->tinyInteger('country')->default(0);
            $table->foreignId('turned_on_by')->nullable()->constrained(UserConstants::TABLE_NAME)->nullOnDelete();
            $table->text('image');
            $table->text('desktop_image')->nullable();
            $table->text('body')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->timestamps();

            // Adding indexes
            $table->index('title');
            $table->index('status');
            $table->index('body');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(PromotionConstants::TABLE_NAME);
    }
};
