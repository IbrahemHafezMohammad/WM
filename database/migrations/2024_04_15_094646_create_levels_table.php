<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Constants\LevelConstants;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(LevelConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->string('level_name');
            $table->integer('level')->unique();
            $table->integer('min')->unique();
            $table->integer('max')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(LevelConstants::TABLE_NAME);
    }
};
