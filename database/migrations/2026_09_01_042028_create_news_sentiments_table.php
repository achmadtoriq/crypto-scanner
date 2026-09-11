<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_sentiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('source')->default('CryptoPanic');
            $table->string('url')->nullable();
            $table->enum('sentiment_label', ['bullish', 'bearish', 'neutral'])->default('neutral');
            $table->decimal('sentiment_score', 4, 2)->default(0.00); // -1.00 to +1.00
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('low');
            $table->text('ai_summary')->nullable();
            $table->string('catalyst')->nullable();
            $table->timestamp('published_at');
            $table->timestamps();

            $table->index(['coin_id', 'sentiment_label', 'created_at']);
            $table->index(['risk_level', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_sentiments');
    }
};
