<?php

use App\Constants\PlayerGameHistoryConstants;
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
        Schema::create(PlayerGameHistoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->boolean('status');
            $table->foreignId('game_platform_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_item_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Adding indexes
            $table->index('note');
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
        Schema::dropIfExists(PlayerGameHistoryConstants::TABLE_NAME);
    }
};
