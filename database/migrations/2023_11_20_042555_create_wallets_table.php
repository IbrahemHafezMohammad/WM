<?php

use App\Constants\GlobalConstants;
use App\Constants\PlayerConstants;
use App\Constants\WalletConstants;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(WalletConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained(PlayerConstants::TABLE_NAME); 
            $table->decimal('base_balance', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->default(0);
            $table->decimal('locked_base_balance', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->tinyInteger('currency')->default(GlobalConstants::CURRENCY_USD);
            $table->softDeletes();
            $table->timestamps();
            
            // Adding indexes
            $table->index('base_balance');
            $table->index('locked_base_balance');
            $table->index('currency');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(WalletConstants::TABLE_NAME);
    }
};
