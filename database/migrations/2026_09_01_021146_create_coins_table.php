<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coins', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique(); // e.g. BTCUSDT
            $table->string('base_asset');        // e.g. BTC
            $table->string('quote_asset')->default('USDT');
            $table->string('exchange')->default('binance'); // binance / bybit
            $table->string('type')->default('perpetual');   // spot / perpetual
            $table->boolean('is_active')->default(true);
            $table->boolean('is_monitored')->default(true);
            $table->decimal('last_price', 20, 8)->nullable();
            $table->decimal('volume_24h', 30, 2)->nullable();
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coins');
    }
};
