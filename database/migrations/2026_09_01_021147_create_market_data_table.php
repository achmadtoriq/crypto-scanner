<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coin_id')->constrained()->cascadeOnDelete();
            $table->string('interval')->default('1h'); // 1h, 4h, 1d
            // OHLCV
            $table->decimal('open', 20, 8);
            $table->decimal('high', 20, 8);
            $table->decimal('low', 20, 8);
            $table->decimal('close', 20, 8);
            $table->decimal('volume', 30, 8);
            $table->decimal('quote_volume', 30, 2)->nullable(); // volume in USDT
            // Market data
            $table->integer('num_trades')->nullable();
            $table->decimal('open_interest', 30, 8)->nullable();
            $table->decimal('funding_rate', 10, 8)->nullable();
            $table->decimal('bid_price', 20, 8)->nullable();
            $table->decimal('ask_price', 20, 8)->nullable();
            $table->timestamp('recorded_at'); // candle open time
            $table->timestamps();

            $table->index(['coin_id', 'interval', 'recorded_at']);
            $table->unique(['coin_id', 'interval', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_data');
    }
};
