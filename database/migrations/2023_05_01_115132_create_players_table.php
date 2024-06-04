<?php

use App\Constants\GlobalConstants;
use App\Constants\PlayerConstants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(PlayerConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('active')->default(PlayerConstants::IS_ACTIVE);
            $table->tinyInteger('type')->default(PlayerConstants::TYPE_NORMAL);
            $table->tinyInteger('language')->default(GlobalConstants::LANG_TL);
            $table->boolean('allow_withdraw')->default(true);
            $table->boolean('allow_betting')->default(true);
            $table->boolean('allow_deposit')->default(true);
            $table->timestamps();

            // Adding indexes
            $table->index('active');
            $table->index('type');
            $table->index('language');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(PlayerConstants::TABLE_NAME);
    }
};
