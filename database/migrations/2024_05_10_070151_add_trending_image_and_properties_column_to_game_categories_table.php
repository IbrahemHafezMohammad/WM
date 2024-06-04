<?php

use App\Constants\GameCategoryConstants;
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
        Schema::table(GameCategoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->integer('properties')->default(0);
            $table->index('properties');
            $table->text('icon_trend')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(GameCategoryConstants::TABLE_NAME, function (Blueprint $table) {
            
        });
    }
};
