<?php

namespace App\Console\Commands;

use App\Models\Coin;
use App\Services\DataFetcherService;
use Illuminate\Console\Command;

class ScannerFetchMarketData extends Command
{
    protected $signature = 'scanner:fetch-market-data
                            {--interval=1h : Timeframe interval}
                            {--limit=60 : Jumlah candle per coin}';

    protected $description = 'Fetch data OHLCV terbaru dari Binance untuk semua coin aktif';

    public function handle(DataFetcherService $fetcher): int
    {
        $interval = $this->option('interval');
        $limit    = (int)$this->option('limit');

        $coins = Coin::active()->get();
        $this->info("📥 Fetching market data for {$coins->count()} coins (interval: {$interval}, limit: {$limit})...");

        // Update ticker dulu
        $tickers = $fetcher->fetch24hTickers();
        $this->line("✅ Tickers updated: " . count($tickers));

        $bar = $this->output->createProgressBar($coins->count());
        $bar->start();

        $stored = 0;
        $failed = 0;

        foreach ($coins as $coin) {
            $count = $fetcher->fetchAndStoreKlines($coin, $interval, $limit);
            if ($count > 0) {
                $stored++;
            } else {
                $failed++;
            }
            $bar->advance();
            usleep(100_000); // 100ms delay
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Market data updated: {$stored} success, {$failed} failed");

        return self::SUCCESS;
    }
}
