<?php

namespace App\Console\Commands;

use App\Services\ScannerService;
use Illuminate\Console\Command;

class ScannerRun extends Command
{
    protected $signature = 'scanner:run
                            {--interval=1h : Timeframe interval (1h, 4h, 1d)}
                            {--dry-run : Jalankan tanpa generate signal}';

    protected $description = 'Jalankan full pipeline Crypto Signal Scanner (fetch → analyze → signal → notify)';

    public function handle(ScannerService $scanner): int
    {
        $interval = $this->option('interval');
        $dryRun   = $this->option('dry-run');

        $this->info("🚀 Starting Crypto Signal Scanner (interval: {$interval})");
        if ($dryRun) {
            $this->warn("⚠️  DRY RUN mode — signals will NOT be saved");
        }

        $this->newLine();

        $bar = $this->output->createProgressBar(7);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');

        $bar->setMessage('Initializing...');
        $bar->start();

        try {
            $bar->setMessage('Running scanner pipeline...');
            $bar->advance();

            $stats = $scanner->run($interval);

            $bar->setMessage('Done!');
            $bar->finish();

            $this->newLine(2);

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Session ID',        $stats['session_id']],
                    ['Interval',          $stats['interval']],
                    ['Coins Total',       $stats['coins_total']],
                    ['Coins Fetched',     $stats['coins_fetched']],
                    ['Passed Screening',  $stats['coins_passed_screen']],
                    ['Signals Generated', $stats['signals_generated']],
                    ['Signals Sent',      $stats['signals_sent']],
                    ['Errors',            $stats['errors']],
                    ['Duration',          $stats['duration_seconds'] . 's'],
                ]
            );

            if ($stats['signals_generated'] > 0) {
                $this->info("✅ {$stats['signals_generated']} signal(s) generated!");
            } else {
                $this->warn("ℹ️  No signals generated this scan.");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error("❌ Scanner failed: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
