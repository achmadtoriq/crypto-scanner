<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\Signal;
use App\Services\ScannerService;
use App\Services\TelegramNotifierService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    /**
     * Webhook entrypoint for Telegram Bot updates.
     */
    public function webhook(Request $request, ScannerService $scanner, TelegramNotifierService $notifier)
    {
        $update = $request->all();
        Log::info("Telegram webhook received:", $update);

        if (!isset($update['message']['text'])) {
            return response()->json(['status' => 'ok']);
        }

        $chatId = $update['message']['chat']['id'];
        $text    = trim($update['message']['text']);
        $cmd     = strtolower(explode(' ', $text)[0]);

        $reply = match (true) {
            $cmd === '/start' || $cmd === '/help' => $this->handleHelp(),
            $cmd === '/scan'                     => $this->handleScan($scanner),
            $cmd === '/stats'                    => $this->handleStats(),
            str_starts_with($cmd, '/')          => $this->handleCoinLookup(substr($cmd, 1)),
            default                             => null,
        };

        if ($reply) {
            $this->sendTelegramReply($chatId, $reply);
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleHelp(): string
    {
        return <<<MSG
        🤖 <b>Crypto Signal Scanner Bot Commands</b>

        /scan — Jalankan scan instan & ambil sinyal terbaru
        /stats — Laporan Win Rate % & statistik performa
        /btc — Cek indikator & AI sentimen BTC
        /eth — Cek indikator & AI sentimen ETH
        /sol — Cek indikator & AI sentimen SOL
        /help — Tampilkan menu bantuan ini
        MSG;
    }

    protected function handleScan(ScannerService $scanner): string
    {
        $stats = $scanner->run('1h');
        $signals = Signal::latest()->limit(3)->get();

        if ($signals->isEmpty()) {
            return "🔍 Scan selesai dalam {$stats['duration_seconds']}s.\nℹ️ Tidak ada sinyal baru yang memenuhi kriteria.";
        }

        $msg = "🚀 <b>Scan Selesai! ({$stats['duration_seconds']}s)</b>\nFound {$stats['signals_generated']} signals:\n\n";
        foreach ($signals as $s) {
            $msg .= "{$s->direction_emoji} <b>{$s->coin->base_asset}/USDT ({$s->direction})</b>\n";
            $msg .= "🎯 Entry: {$s->entry_price} | Confidence: {$s->confidence_score}%\n\n";
        }
        return $msg;
    }

    protected function handleStats(): string
    {
        $totalClosed = Signal::closedOutcome()->count();
        $totalWins   = Signal::wins()->count();
        $totalLosses = Signal::losses()->count();
        $winRate     = $totalClosed > 0 ? round(($totalWins / $totalClosed) * 100, 1) : 0;
        $totalPnl    = Signal::closedOutcome()->sum('pnl_pct');

        return <<<MSG
        📊 <b>Bot Performance & Win Rate Report</b>

        🏆 <b>Win Rate: {$winRate}%</b>
        ✅ Total Wins: {$totalWins}
        🛑 Total Losses: {$totalLosses}
        📈 Total PnL: {$totalPnl}% (with 10x leverage)
        ⏳ Total Tracked Signals: {$totalClosed}
        MSG;
    }

    protected function handleCoinLookup(string $symbol): ?string
    {
        $symbol = strtoupper($symbol) . (str_ends_with(strtoupper($symbol), 'USDT') ? '' : 'USDT');
        $coin = Coin::where('symbol', $symbol)->with(['latestIndicator', 'latestNewsSentiment'])->first();

        if (!$coin) return null;

        $ind  = $coin->latestIndicator;
        $news = $coin->latestNewsSentiment;
        $price = number_format((float)$coin->last_price, 4);

        $rsiStr = $ind ? number_format((float)$ind->rsi, 1) : 'N/A';
        $maStr  = $ind && $ind->is_above_ma20 ? 'Bullish (Above MA20)' : 'Bearish (Below MA20)';
        $newsStr = $news ? "{$news->sentiment_emoji}: {$news->ai_summary}" : 'Belum ada berita baru.';

        return <<<MSG
        🪙 <b>{$coin->base_asset} / USDT Analysis</b>

        💰 Price: <b>\${$price}</b>
        📊 RSI(14): {$rsiStr}
        📈 MA Trend: {$maStr}
        📰 AI Insight: {$newsStr}
        MSG;
    }

    protected function sendTelegramReply(int $chatId, string $message): void
    {
        $token = config('scanner.telegram.bot_token');
        if (empty($token)) return;

        try {
            $client = new Client(['timeout' => 10]);
            $client->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'json' => [
                    'chat_id'    => $chatId,
                    'text'       => $message,
                    'parse_mode' => 'HTML',
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send Telegram reply: " . $e->getMessage());
        }
    }
}
