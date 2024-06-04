<?php

use App\Constants\NotificationConstants;
use App\Constants\PlayerConstants;
use App\Constants\PlayerNotificationConstants;
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
        Schema::table(PlayerNotificationConstants::TABLE_NAME, function (Blueprint $table) {
            $table->foreign('notification_id')->references('id')->on(NotificationConstants::TABLE_NAME)->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on(PlayerConstants::TABLE_NAME)->onDelete('cascade');
        });
    }

};
