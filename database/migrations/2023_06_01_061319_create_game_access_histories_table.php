<?php

use App\Constants\GameAccessHistoryConstants;
use App\Constants\GameItemConstants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(GameAccessHistoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->text('remark')->nullable();
            $table->tinyInteger('status')->default(GameAccessHistoryConstants::STATUS_PENDING);
            $table->foreignId('game_item_id')->constrained(GameItemConstants::TABLE_NAME)->cascadeOnDelete();
            $table->timestamps();

            // Adding indexes
            $table->index('remark');
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(GameAccessHistoryConstants::TABLE_NAME);
    }
};
