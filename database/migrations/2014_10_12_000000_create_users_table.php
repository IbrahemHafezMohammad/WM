<?php

use App\Constants\UserConstants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(UserConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('user_name')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->text('remark')->nullable();
            $table->text('profile_pic')->nullable();
            $table->tinyInteger('gender')->default(UserConstants::GENDER_UNKNOWN);
            $table->date('birthday')->nullable();
            $table->rememberToken();
            $table->timestamps();

            // Adding indexes
            $table->index('name');
            $table->index('user_name');
            $table->index('phone');
            $table->index('gender');
            $table->index('created_at');
            $table->index('updated_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(UserConstants::TABLE_NAME);
    }
};
