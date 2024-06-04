<?php

use App\Constants\AdminConstants;
use App\Constants\GlobalConstants;
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
        Schema::create(AdminConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('lang')->default(GlobalConstants::LANG_EN);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('status')->default(true);
            $table->boolean('is_2fa_enabled')->default(false);
            $table->text('google2fa_secret')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(AdminConstants::TABLE_NAME);
    }
};
