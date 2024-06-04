<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Constants\PaymentCategoryConstants;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(PaymentCategoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('icon');
            $table->json('public_name');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(PaymentCategoryConstants::TABLE_NAME);
    }
};
