<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Constants\ProviderIPWhitelistConstants;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(ProviderIPWhitelistConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->string('provider_code');
            $table->ipAddress('ip_address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ProviderIPWhitelistConstants::TABLE_NAME);
    }
};
