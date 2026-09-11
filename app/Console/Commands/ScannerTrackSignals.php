<?php

namespace App\Console\Commands;

use App\Services\SignalTrackerService;
use Illuminate\Console\Command;

class ScannerTrackSignals extends Command
{
    protected $signature   = 'scanner:track';
    protected $description = 'Jalankan tracking otomatis pergerakan harga vs Entry, SL, dan TP1-3 untuk semua sinyal aktif';

    public function handle(SignalTrackerService $tracker): int
    {
        $this->info("🎯 Running Signal Outcome Tracker...");

        $stats = $tracker->trackAllActiveSignals();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Active Signals', $stats['total_active']],
                ['Outcomes Updated',     $stats['updated']],
                ['Hit TP (TP1/2/3)',     $stats['hit_tp']],
                ['Hit SL',               $stats['hit_sl']],
                ['Expired (>48h)',       $stats['expired']],
            ]
        );

        $this->info("✅ Signal tracking complete!");

        return self::SUCCESS;
    }
}
