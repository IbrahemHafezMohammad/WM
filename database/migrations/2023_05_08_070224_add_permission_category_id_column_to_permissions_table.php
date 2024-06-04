<?php

use App\Constants\PermissionConstants;
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
        Schema::table(PermissionConstants::TABLE_NAME, function (Blueprint $table) {
            $table->foreignId('permission_category_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(PermissionConstants::TABLE_NAME, function (Blueprint $table) {
            $table->dropColumn('permission_category_id');
        });
    }
};
