<?php

use App\Constants\GameItemConstants;
use Illuminate\Support\Facades\Schema;
use App\Constants\GameCategoryConstants;
use App\Constants\GamePlatformConstants;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(GameItemConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_platform_id')->constrained(GamePlatformConstants::TABLE_NAME)->cascadeOnDelete();
            $table->json('name');
            $table->string('game_id')->unique();
            $table->text('icon_square')->nullable();
            $table->text('icon_rectangle')->nullable();
            $table->text('icon_square_desktop')->nullable();
            $table->text('icon_rectangle_desktop')->nullable();
            $table->tinyInteger('status')->default(GameItemConstants::STATUS_ACTIVE);
            $table->integer('properties')->default(0);
            $table->integer('supported_currencies')->default(0);
            $table->timestamps();

            // Adding indexes
            $table->index('name');
            $table->index('game_id');
            $table->index('status');
            $table->index('properties');
            $table->index('supported_currencies');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(GameItemConstants::TABLE_NAME);
    }
};
