<?php

namespace App\Console\Commands;

use App\Services\AiNewsSentimentService;
use Illuminate\Console\Command;

class ScannerFetchNews extends Command
{
    protected $signature   = 'scanner:fetch-news';
    protected $description = 'Fetch & analisa sentimen berita kripto terbaru menggunakan Gemini AI';

    public function handle(AiNewsSentimentService $newsService): int
    {
        $this->info("🤖 Fetching & analyzing crypto news with Gemini AI...");

        $res = $newsService->fetchAndAnalyzeNews();
        $sentiment = $newsService->getOverallMarketSentiment();

        $this->newLine();
        $this->info("📊 Market Sentiment Index: {$sentiment['score']}% — {$sentiment['label']}");
        $this->line("✅ Saved {$res['saved']} news items to database.");

        return self::SUCCESS;
    }
}
