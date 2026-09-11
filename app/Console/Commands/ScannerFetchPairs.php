<?php

namespace App\Console\Commands;

use App\Services\DataFetcherService;
use Illuminate\Console\Command;

class ScannerFetchPairs extends Command
{
    protected $signature   = 'scanner:fetch-pairs';
    protected $description = 'Sync daftar pair USDT dari Binance ke database';

    public function handle(DataFetcherService $fetcher): int
    {
        $this->info("🔄 Syncing USDT pairs from Binance...");

        $synced = $fetcher->fetchAndSyncPairs();

        $this->info("✅ Synced " . count($synced) . " pairs:");
        $this->line(implode(', ', array_slice($synced, 0, 10)) . (count($synced) > 10 ? '...' : ''));

        return self::SUCCESS;
    }
}
