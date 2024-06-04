<?php

use App\Constants\AdminConstants;
use Illuminate\Support\Facades\Schema;
use App\Constants\PlayerRatingConstants;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(PlayerRatingConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained(AdminConstants::TABLE_NAME)->nullOnDelete();
            $table->text('comment')->nullable();
            $table->tinyInteger('department');
            $table->float('rating');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(PlayerRatingConstants::TABLE_NAME);
    }
};
