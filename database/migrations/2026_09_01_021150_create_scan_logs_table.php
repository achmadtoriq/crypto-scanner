<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stage'); // fetch, calculate, screen, analyze, signal, notify
            $table->enum('status', ['pass', 'skip', 'error', 'info']);
            $table->string('message')->nullable();
            $table->json('context')->nullable(); // Additional data as JSON
            $table->string('session_id')->nullable(); // Group logs per scan run
            $table->timestamps();

            $table->index(['stage', 'status', 'created_at']);
            $table->index(['session_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
};
