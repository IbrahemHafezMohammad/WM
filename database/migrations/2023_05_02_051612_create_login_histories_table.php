<?php

use App\Constants\LoginHistoryConstants;
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
        Schema::create(LoginHistoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip', LoginHistoryConstants::IP_MAX_LENGTH);
            $table->string('device_type')->nullable();
            $table->string('browser_type')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            // Adding indexes
            $table->index('ip');
            $table->index('device_type');
            $table->index('browser_type');
            $table->index('note');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LoginHistoryConstants::TABLE_NAME);
    }
};
