<?php

use App\Constants\BetConstants;
use App\Constants\BetRoundConstants;
use App\Constants\GameItemConstants;
use App\Constants\GlobalConstants;
use App\Constants\PlayerConstants;
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
        Schema::create(BetConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('bet_round_id')->constrained(BetRoundConstants::TABLE_NAME);
            $table->string('bet_round_reference')->nullable();
            $table->foreignId('game_item_id')->constrained(GameItemConstants::TABLE_NAME);
            $table->string('game_code')->nullable();
            $table->string('bet_reference');
            $table->dateTime('bet_on');
            $table->dateTime('closed_on')->nullable();
            $table->tinyInteger('status');
            $table->decimal('bet_amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION);
            $table->decimal('valid_bet', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION);
            $table->decimal('turnover', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->decimal('win_amount', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->decimal('win_loss', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->decimal('rebate', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->tinyInteger('currency');
            $table->string('odds')->nullable();
            $table->string('comm')->nullable();
            $table->json('lifecycle')->nullable();
            $table->timestamps();

            //add indexes
            $table->index('bet_reference');
            $table->index('bet_on');
            $table->index('closed_on');
            $table->index('status');
            $table->index('bet_amount'); // Text fields can be indexed but have some limitations
            $table->index('valid_bet');
            $table->index('turnover');
            $table->index('win_amount');
            $table->index('win_loss');
            $table->index('rebate');
            $table->index('currency');
            $table->index('odds');
            $table->index('comm');
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
        Schema::dropIfExists(BetConstants::TABLE_NAME);
    }
};
