<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\ScanLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * ScannerService — Main Orchestrator
 * Mengorkestrasi semua stage pipeline dari news fetch hingga notify.
 */
class ScannerService
{
    protected string $sessionId;

    public function __construct(
        protected DataFetcherService $fetcher,
        protected AiNewsSentimentService $newsService,
        protected IndicatorEngineService $indicators,
        protected ScreeningService $screener,
        protected TechnicalAnalysisService $analyzer,
        protected SignalGeneratorService $generator,
        protected TelegramNotifierService $notifier,
    ) {
        $this->sessionId = Str::uuid()->toString();
    }

    /**
     * Jalankan full pipeline scan.
     */
    public function run(string $interval = '1h'): array
    {
        $startTime = microtime(true);
        $stats = [
            'session_id'    => $this->sessionId,
            'interval'      => $interval,
            'coins_total'   => 0,
            'coins_fetched' => 0,
            'coins_passed_screen' => 0,
            'signals_generated'   => 0,
            'signals_sent'        => 0,
            'errors'              => 0,
            'duration_seconds'    => 0,
        ];

        $this->log(null, 'info', 'info', "🚀 Scanner session started: {$this->sessionId}");

        // =============================================
        // Stage 0: AI News & Sentiment Ingestion
        // =============================================
        try {
            $this->log(null, 'fetch', 'info', "Stage 0: Fetching AI News & Market Sentiment...");
            $newsStats = $this->newsService->fetchAndAnalyzeNews();
            $this->log(null, 'fetch', 'pass', "AI Analyzed {$newsStats['saved']} news items.");
        } catch (\Exception $e) {
            $this->log(null, 'fetch', 'error', "AI News fetch error: " . $e->getMessage());
        }

        // =============================================
        // Stage 1: Sync Pairs & Fetch Ticker
        // =============================================
        try {
            $this->log(null, 'fetch', 'info', "Stage 1: Syncing pairs from Binance...");
            $synced = $this->fetcher->fetchAndSyncPairs();
            $this->log(null, 'fetch', 'pass', "Synced " . count($synced) . " pairs from Binance");

            $this->log(null, 'fetch', 'info', "Fetching 24h tickers...");
            $tickers = $this->fetcher->fetch24hTickers();
            $this->log(null, 'fetch', 'pass', "Fetched tickers for " . count($tickers) . " coins");
        } catch (\Exception $e) {
            $this->log(null, 'fetch', 'error', "Stage 1 failed: " . $e->getMessage());
            $stats['errors']++;
        }

        // Stage 1b: Fetch OHLCV
        $coins = Coin::active()->get();
        $stats['coins_total'] = $coins->count();

        foreach ($coins as $coin) {
            try {
                $stored = $this->fetcher->fetchAndStoreKlines($coin, $interval, 60);
                if ($stored > 0) {
                    $stats['coins_fetched']++;
                    $this->log($coin, 'fetch', 'pass', "Fetched {$stored} klines for {$coin->symbol}");
                }
            } catch (\Exception $e) {
                $this->log($coin, 'fetch', 'error', "Klines fetch error {$coin->symbol}: " . $e->getMessage());
                $stats['errors']++;
            }
            usleep(50_000);
        }

        // Stage 2: Calculate Indicators
        $this->log(null, 'calculate', 'info', "Stage 2: Calculating indicators...");
        foreach ($coins as $coin) {
            try {
                $indicator = $this->indicators->calculate($coin, $interval);
                if ($indicator) {
                    $this->log($coin, 'calculate', 'pass', "Indicators calculated for {$coin->symbol}. RSI: {$indicator->rsi}");
                }
            } catch (\Exception $e) {
                $this->log($coin, 'calculate', 'error', "Indicator error {$coin->symbol}: " . $e->getMessage());
                $stats['errors']++;
            }
        }

        // Stage 3: Screening & News Risk Filter
        $this->log(null, 'screen', 'info', "Stage 3: Screening coins & AI News Risk...");
        $passedCoins = [];

        foreach ($coins as $coin) {
            $indicator = $coin->latestIndicator;
            if (!$indicator) continue;

            $screenResult = $this->screener->screen($coin, $indicator);
            if ($screenResult['pass']) {
                $this->log($coin, 'screen', 'pass', "{$coin->symbol}: " . $screenResult['reason']);
                $passedCoins[] = ['coin' => $coin, 'indicator' => $indicator];
                $stats['coins_passed_screen']++;
            } else {
                $this->log($coin, 'screen', 'skip', "{$coin->symbol}: " . $screenResult['reason']);
            }
        }

        // Stage 4-6: Analyze + Signal Gen
        $this->log(null, 'analyze', 'info', "Stage 4-6: Technical analysis + signal generation...");
        foreach ($passedCoins as $item) {
            $coin      = $item['coin'];
            $indicator = $item['indicator'];
            $coin->refresh();

            try {
                $analysis = $this->analyzer->analyze($coin, $indicator);
                if (!$analysis['valid']) continue;

                $signal = $this->generator->generate($coin, $indicator, $analysis);
                if ($signal) {
                    $stats['signals_generated']++;
                    $this->log($coin, 'signal', 'pass',
                        "Signal generated: {$coin->symbol} {$signal->direction} ({$signal->tier}) @ {$signal->entry_price}"
                    );
                }
            } catch (\Exception $e) {
                $this->log($coin, 'analyze', 'error', "Analysis error {$coin->symbol}: " . $e->getMessage());
                $stats['errors']++;
            }
        }

        // Stage 7: Notification
        $this->log(null, 'notify', 'info', "Stage 7: Sending Telegram notifications...");
        try {
            $notifyResults = $this->notifier->sendPending();
            $stats['signals_sent'] = $notifyResults['sent'];
        } catch (\Exception $e) {
            $this->log(null, 'notify', 'error', "Notification error: " . $e->getMessage());
        }

        $stats['duration_seconds'] = round(microtime(true) - $startTime, 2);
        $this->log(null, 'info', 'info', "✅ Scan completed in {$stats['duration_seconds']}s.");

        return $stats;
    }

    protected function log(?Coin $coin, string $stage, string $status, string $message): void
    {
        ScanLog::create([
            'coin_id'    => $coin?->id,
            'stage'      => $stage,
            'status'     => $status,
            'message'    => $message,
            'session_id' => $this->sessionId,
        ]);

        $prefix = $coin ? "[{$coin->symbol}]" : "[SYSTEM]";
        Log::info("{$prefix} [{$stage}] [{$status}] {$message}");
    }
}
