<?php

use App\Constants\PlayerConstants;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Constants\UGProviderConfigConstants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(UGProviderConfigConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained(PlayerConstants::TABLE_NAME);
            $table->string('odds_expression')->default(UGProviderConfigConstants::ODDS_EXPRESSION_DECIMAL);
            $table->string('template')->default(UGProviderConfigConstants::TEMPLATE_STANDARD);
            $table->string('theme')->default(UGProviderConfigConstants::THEME_CLASSIC_BLUE);
            $table->integer('game_mode')->default(UGProviderConfigConstants::GAME_MODE_ALL_SPORTS);
            $table->integer('favorite_sport')->default(UGProviderConfigConstants::FAVORITE_SPORT_SOCCER);
            $table->string('default_market')->default(UGProviderConfigConstants::DEFAULT_MARKET_FAST);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(UGProviderConfigConstants::TABLE_NAME);
    }
};
