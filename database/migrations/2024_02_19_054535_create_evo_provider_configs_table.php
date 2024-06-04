<?php

use App\Constants\PlayerConstants;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Constants\EVOProviderConfigConstants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(EVOProviderConfigConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained(PlayerConstants::TABLE_NAME);
            $table->string('group_id')->default(EVOProviderConfigConstants::GROUP_ID_BASIC);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(EVOProviderConfigConstants::TABLE_NAME);
    }
};
