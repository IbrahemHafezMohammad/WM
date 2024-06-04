<?php

use App\Constants\AdminConstants;
use App\Constants\NotificationConstants;
use App\Constants\PlayerConstants;
use App\Constants\PlayerNotificationConstants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(PlayerNotificationConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('player_id');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(PlayerNotificationConstants::TABLE_NAME);
    }
};
