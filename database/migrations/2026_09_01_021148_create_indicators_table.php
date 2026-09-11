<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coin_id')->constrained()->cascadeOnDelete();
            $table->string('interval')->default('1h');

            // Stage 2: Basic Indicators
            $table->decimal('price_change_pct', 10, 4)->nullable();  // % perubahan harga
            $table->decimal('volume_spike', 10, 4)->nullable();       // volume / avg_volume
            $table->decimal('oi_change_pct', 10, 4)->nullable();      // % perubahan OI
            $table->decimal('spread_pct', 10, 6)->nullable();         // (ask-bid)/bid * 100
            $table->decimal('atr', 20, 8)->nullable();                // Average True Range

            // Stage 4: Technical Indicators
            $table->decimal('ma20', 20, 8)->nullable();               // MA 20
            $table->decimal('ma50', 20, 8)->nullable();               // MA 50
            $table->decimal('rsi', 8, 4)->nullable();                 // RSI (0-100)
            $table->decimal('stoch_k', 8, 4)->nullable();             // Stochastic %K
            $table->decimal('stoch_d', 8, 4)->nullable();             // Stochastic %D
            $table->decimal('avg_volume_20', 30, 8)->nullable();      // Avg volume 20 candles

            // Calculated flags
            $table->boolean('is_above_ma20')->default(false);
            $table->boolean('is_above_ma50')->default(false);
            $table->boolean('is_volume_spike')->default(false);
            $table->boolean('is_oi_increasing')->default(false);
            $table->boolean('is_rsi_healthy')->default(false);        // RSI 50-70

            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['coin_id', 'interval', 'calculated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
