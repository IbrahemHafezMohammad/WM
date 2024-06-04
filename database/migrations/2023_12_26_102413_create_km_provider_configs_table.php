<?php

use App\Constants\PlayerConstants;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Constants\KMProviderConfigConstants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(KMProviderConfigConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained(PlayerConstants::TABLE_NAME);
            $table->string('vndk_user_id')->unique()->index();
            $table->string('inr_user_id')->unique()->index();
            $table->string('php_user_id')->unique()->index();
            $table->tinyInteger('bet_limit')->default(KMProviderConfigConstants::BET_LIMIT_BASIC);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(KMProviderConfigConstants::TABLE_NAME);
    }
};
