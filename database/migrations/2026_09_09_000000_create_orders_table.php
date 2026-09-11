<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id')->unique();
            $table->foreignId('signal_id')->nullable()->constrained('signals')->onDelete('set null');
            $table->foreignId('coin_id')->nullable()->constrained('coins')->onDelete('cascade');
            $table->string('symbol');
            $table->enum('direction', ['LONG', 'SHORT']);
            $table->string('exchange')->default('Binance Futures');
            $table->enum('order_type', ['LIMIT', 'MARKET'])->default('LIMIT');
            $table->decimal('entry_price', 20, 8);
            $table->decimal('stop_loss', 20, 8);
            $table->decimal('take_profit', 20, 8)->nullable();
            $table->decimal('margin_usd', 15, 2)->default(0);
            $table->decimal('position_size_usd', 15, 2)->default(0);
            $table->integer('leverage')->default(10);
            $table->enum('status', ['EXECUTED', 'FILLED', 'CLOSED', 'CANCELLED'])->default('EXECUTED');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
