<?php

use App\Constants\GameItemConstants;
use App\Constants\GamePlatformConstants;
use App\Constants\PaymentMethodConstants;
use Illuminate\Support\Facades\Schema;
use App\Constants\ApiHitConstants;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(ApiHitConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->json('request');
            $table->json('response')->nullable();
            $table->json('extra_data')->nullable();
            $table->string('api_endpoint');
            $table->string('request_method');
            $table->json('authorization')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->string('content_type')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->integer('status_code')->nullable();
            $table->text('exception')->nullable();
            $table->foreignId('game_item_id')->nullable()->constrained(GameItemConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('game_platform_id')->nullable()->constrained(GamePlatformConstants::TABLE_NAME)->nullOnDelete();
            $table->string('payment_method')->nullable();
            $table->bigInteger('request_start_timestamp_ms');
            $table->bigInteger('request_end_timestamp_ms');
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            // Adding indexes
            $table->index('request');
            $table->index('response');
            $table->index('api_endpoint');
            $table->index('request_method');
            $table->index('authorization');
            $table->index('user_agent');
            $table->index('referer');
            $table->index('content_type');
            $table->index('ip_address');
            $table->index('status_code');
            $table->index('exception');
            $table->index('request_start_timestamp_ms');
            $table->index('request_end_timestamp_ms');
            $table->index('duration_ms');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(ApiHitConstants::TABLE_NAME);
    }
};
