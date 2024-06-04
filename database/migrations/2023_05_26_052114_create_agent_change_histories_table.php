<?php

use App\Constants\AdminConstants;
use App\Constants\AgentChangeHistoryConstants;
use App\Constants\AgentConstants;
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
        Schema::create(AgentChangeHistoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('previous_agent_id')->nullable()->constrained(AgentConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('new_agent_id')->constrained(AgentConstants::TABLE_NAME)->cascadeOnDelete();
            $table->foreignId('change_by')->constrained(AdminConstants::TABLE_NAME)->cascadeOnDelete();
            $table->timestamps();

            // Adding indexes
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(AgentChangeHistoryConstants::TABLE_NAME);
    }
};
