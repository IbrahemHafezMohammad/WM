<?php

use App\Constants\GamePlatformConstants;
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
        Schema::create(GamePlatformConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('icon_image')->nullable();
            $table->string('platform_code')->unique();
            $table->timestamps();

            // Adding indexes
            $table->index('name');
            $table->index('platform_code');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(GamePlatformConstants::TABLE_NAME);
    }
};
