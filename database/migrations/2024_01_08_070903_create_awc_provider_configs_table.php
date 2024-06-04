<?php

use App\Constants\PlayerConstants;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Constants\AWCProviderConfigConstants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(AWCProviderConfigConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained(PlayerConstants::TABLE_NAME);
            $table->string('user_id')->unique();
            $table->boolean('auto_bet_mode')->default(true);
            $table->json('vndk_bet_limit');
            $table->json('php_bet_limit');
            $table->json('inr_bet_limit');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(AWCProviderConfigConstants::TABLE_NAME);
    }
};
