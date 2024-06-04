<?php

use App\Constants\AgentConstants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(AgentConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('senior_agent_id')->nullable()->constrained(AgentConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('unique_code')->nullable();
            $table->timestamps();

            // Adding indexes
            $table->index('unique_code');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(AgentConstants::TABLE_NAME);
    }
};
