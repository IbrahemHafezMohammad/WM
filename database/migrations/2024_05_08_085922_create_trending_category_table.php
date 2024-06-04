<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('trending_category', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->unsignedBigInteger('game_category_id'); // Foreign key to category
            $table->boolean('status')->default(true); // Example status (true for active, false for inactive)
            $table->integer('sort_order')->default(0); // Sort order for display
            $table->string('active_image')->nullable(); // URL or path to active image
            $table->string('inactive_image')->nullable(); // URL or path to inactive image
            $table->timestamps();

            // If there's a category table, establish a foreign key relationship
            $table->foreign('game_category_id')->references('id')->on('game_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trending_category');
    }
};
