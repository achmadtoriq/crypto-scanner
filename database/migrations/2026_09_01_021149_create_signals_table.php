<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coin_id')->constrained()->cascadeOnDelete();

            // Stage 5: Signal Generation
            $table->enum('direction', ['LONG', 'SHORT']);
            $table->decimal('entry_price', 20, 8);
            $table->decimal('stop_loss', 20, 8);
            $table->decimal('take_profit_1', 20, 8);
            $table->decimal('take_profit_2', 20, 8);
            $table->decimal('take_profit_3', 20, 8);

            // Stage 6: Risk & Position Setup
            $table->integer('leverage')->default(10);
            $table->decimal('confidence_score', 5, 2)->default(0); // 0-100

            // Notification
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->text('telegram_message')->nullable();
            $table->string('chart_url')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('sent_at')->nullable();

            // Reference snapshot
            $table->string('interval')->default('1h');
            $table->decimal('atr_at_signal', 20, 8)->nullable();
            $table->decimal('rsi_at_signal', 8, 4)->nullable();
            $table->decimal('volume_spike_at_signal', 10, 4)->nullable();

            $table->timestamps();

            $table->index(['coin_id', 'direction', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};
