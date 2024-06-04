<?php

use App\Constants\BetConstants;
use App\Constants\UserConstants;
use App\Constants\AdminConstants;
use App\Constants\GlobalConstants;
use App\Constants\PlayerConstants;
use App\Constants\WalletConstants;
use App\Constants\BetRoundConstants;
use App\Constants\GameItemConstants;
use Illuminate\Support\Facades\Schema;
use App\Constants\GamePlatformConstants;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Constants\GameTransactionHistoryConstants;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(GameTransactionHistoryConstants::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->decimal('points', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION);
            $table->decimal('before_points', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION);
            $table->decimal('after_points', GlobalConstants::DECIMAL_TOTALS, GlobalConstants::DECIMAL_PRECISION)->nullable();
            $table->foreignId('game_item_id')->nullable()->constrained(GameItemConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('game_platform_id')->nullable()->constrained(GamePlatformConstants::TABLE_NAME)->nullOnDelete();
            $table->tinyInteger('currency');
            $table->text('remark')->nullable();
            $table->foreignId('player_id')->nullable()->constrained(PlayerConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('bet_id')->nullable()->constrained(BetConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('bet_round_id')->nullable()->constrained(BetRoundConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('action_by')->nullable()->constrained(AdminConstants::TABLE_NAME)->nullOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained(WalletConstants::TABLE_NAME)->nullOnDelete();
            $table->tinyInteger('status')->default(GameTransactionHistoryConstants::STATUS_PROCESSING);
            $table->string('transaction_request_no')->nullable();
            $table->string('game_transaction_no')->nullable();
            $table->foreignId('refer_to')->nullable()->constrained(GameTransactionHistoryConstants::TABLE_NAME)->nullOnDelete();
            $table->string('reference_no')->nullable();
            $table->tinyInteger('transaction_type');
            $table->boolean('is_withdraw');
            $table->timestamps();

            // add a generated column to ensure uniqueness
            // $table->string('unique_reference_no')->virtualAs('CASE WHEN status = ' . GameTransactionHistoryConstants::STATUS_SUCCESS . ' THEN reference_no END')->nullable();
            // $table->unique('unique_reference_no');

            // Adding indexes
            $table->index('points');
            $table->index('before_points');
            $table->index('after_points');
            $table->index('currency');
            $table->index('remark'); // Text fields can be indexed but have some limitations
            $table->index('status');
            $table->index('game_transaction_no');
            $table->index('reference_no');
            $table->index('transaction_type');
            $table->index('is_withdraw');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(GameTransactionHistoryConstants::TABLE_NAME);
    }
};
