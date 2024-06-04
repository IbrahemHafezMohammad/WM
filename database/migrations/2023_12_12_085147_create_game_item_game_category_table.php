<?php

use App\Constants\GameItemConstants;
use Illuminate\Support\Facades\Schema;
use App\Constants\GameCategoryConstants;
use Illuminate\Database\Schema\Blueprint;
use App\Constants\GameItemGameCategoryConstants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(GameItemGameCategoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_item_id')->constrained(GameItemConstants::TABLE_NAME)->cascadeOnDelete();
            $table->foreignId('game_category_id')->constrained(GameCategoryConstants::TABLE_NAME)->cascadeOnDelete();
            $table->integer('game_item_sort_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(GameItemGameCategoryConstants::TABLE_NAME);
    }
};
