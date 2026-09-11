<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->enum('outcome', ['pending', 'hit_tp1', 'hit_tp2', 'hit_tp3', 'hit_sl', 'expired'])
                  ->default('pending')
                  ->after('status');
            $table->enum('tier', ['VIP', 'STANDARD'])
                  ->default('STANDARD')
                  ->after('outcome');
            $table->decimal('highest_price_reached', 20, 8)->nullable()->after('tier');
            $table->decimal('lowest_price_reached', 20, 8)->nullable()->after('highest_price_reached');
            $table->decimal('pnl_pct', 10, 2)->nullable()->after('lowest_price_reached');
            $table->timestamp('closed_at')->nullable()->after('pnl_pct');

            $table->index(['outcome', 'created_at']);
            $table->index(['tier', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->dropColumn([
                'outcome', 'tier', 'highest_price_reached',
                'lowest_price_reached', 'pnl_pct', 'closed_at'
            ]);
        });
    }
};
