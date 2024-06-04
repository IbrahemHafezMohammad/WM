<?php

use App\Constants\ApiHitConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(ApiHitConstants::TABLE_NAME, function (Blueprint $table) {
            // Change the columns to FLOAT
            DB::statement('ALTER TABLE api_hits MODIFY COLUMN request_start_timestamp_ms VARCHAR(255)');
            DB::statement('ALTER TABLE api_hits MODIFY COLUMN request_end_timestamp_ms VARCHAR(255)');
            DB::statement('ALTER TABLE api_hits MODIFY COLUMN duration_ms VARCHAR(255) NULL');        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(ApiHitConstants::TABLE_NAME, function (Blueprint $table) {
            //
        });
    }
};
