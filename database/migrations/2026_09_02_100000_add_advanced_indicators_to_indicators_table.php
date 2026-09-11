<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            // EMA indicators
            $table->decimal('ema9', 20, 8)->nullable()->after('ma50');
            $table->decimal('ema21', 20, 8)->nullable()->after('ema9');
            // MACD (12, 26, 9)
            $table->decimal('macd_line', 20, 8)->nullable()->after('ema21');
            $table->decimal('macd_signal', 20, 8)->nullable()->after('macd_line');
            $table->decimal('macd_histogram', 20, 8)->nullable()->after('macd_signal');
            // Bollinger Bands (20, 2)
            $table->decimal('bb_upper', 20, 8)->nullable()->after('macd_histogram');
            $table->decimal('bb_lower', 20, 8)->nullable()->after('bb_upper');
            $table->decimal('bb_pct_b', 8, 4)->nullable()->after('bb_lower'); // 0-100, where in BB
            // Flags
            $table->boolean('is_macd_bullish')->default(false)->after('is_rsi_healthy');
            $table->boolean('is_above_ema9')->default(false)->after('is_macd_bullish');
            $table->boolean('is_above_ema21')->default(false)->after('is_above_ema9');
            $table->boolean('is_bb_squeeze')->default(false)->after('is_above_ema21'); // BB width < 2%
        });
    }

    public function down(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->dropColumn(['ema9','ema21','macd_line','macd_signal','macd_histogram',
                'bb_upper','bb_lower','bb_pct_b','is_macd_bullish','is_above_ema9','is_above_ema21','is_bb_squeeze']);
        });
    }
};
