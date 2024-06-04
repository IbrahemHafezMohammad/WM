<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Constants\AdminConstants;
use App\Constants\PlayerConstants;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(PlayerConstants::TABLE_NAME, function (Blueprint $table) {
            $table->foreignId('player_rating_id')->nullable()->constrained(AdminConstants::TABLE_NAME)->nullOnDelete();
        });
    }
    
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            //
        });
    }
};
