<?php

use App\Constants\GlobalConstants;
use App\Constants\PlayerConstants;
use App\Constants\BetRoundConstants;
use Illuminate\Support\Facades\Schema;
use App\Constants\GamePlatformConstants;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(BetRoundConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained(PlayerConstants::TABLE_NAME);
            $table->foreignId('game_platform_id')->nullable()->constrained(GamePlatformConstants::TABLE_NAME)->nullOnDelete();
            $table->string('provider')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('device')->nullable();
            $table->string('round_reference');
            $table->dateTime('started_on');
            $table->dateTime('ended_on')->nullable();
            $table->tinyInteger('status')->default(BetRoundConstants::STATUS_OPEN);
            $table->tinyInteger('currency');
            $table->decimal('win_loss', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->decimal('total_valid_bets', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->decimal('total_turnovers', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->decimal('total_win_amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->dateTime('expected_winloss_on')->nullable();
            $table->json('lifecycle')->nullable();
            $table->timestamps();

            //add indexes
            $table->index('provider');
            $table->index('ip_address');
            $table->index('device');
            $table->index('round_reference'); // Text fields can be indexed but have some limitations
            $table->index('started_on');
            $table->index('ended_on');
            $table->index('status');
            $table->index('currency');
            $table->index('win_loss');
            $table->index('lifecycle');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(BetRoundConstants::TABLE_NAME);
    }
};
