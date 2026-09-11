<?php

namespace App\Services;

use App\Models\Signal;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Stage 7: Notification Service
 * Mengirim sinyal ke Telegram Bot.
 * Jika TELEGRAM_BOT_TOKEN kosong, sinyal disimpan sebagai 'pending' saja.
 */
class TelegramNotifierService
{
    protected ?string $botToken;
    protected ?string $chatId;
    protected Client $http;

    public function __construct()
    {
        $dbToken = \App\Models\Setting::get('telegram.bot_token') ?: \App\Models\Setting::get('telegram_bot_token');
        $dbChat  = \App\Models\Setting::get('telegram.chat_id') ?: \App\Models\Setting::get('telegram_chat_id');

        $this->botToken = $dbToken ?: (config('scanner.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN'));
        $this->chatId   = $dbChat ?: (config('scanner.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID'));
        $this->http     = new Client(['timeout' => 15]);
    }

    /**
     * Kirim sinyal ke Telegram.
     * @return bool success
     */
    public function send(Signal $signal): bool
    {
        if (!$this->isConfigured()) {
            Log::info("Telegram not configured. Signal #{$signal->id} will remain as 'pending'.");
            return false;
        }

        $message = $signal->telegram_message;
        if (!$message) {
            Log::warning("Signal #{$signal->id} has no telegram_message.");
            return false;
        }

        try {
            $webUrl = config('app.url', 'http://localhost:8000') . "/signals/{$signal->id}";
            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        ['text' => '🧮 Calc Position Size', 'url' => $webUrl],
                        ['text' => '⚡ Open Web App', 'url' => $webUrl],
                    ],
                    [
                        ['text' => '📈 TradingView Chart', 'url' => "https://www.tradingview.com/chart/?symbol=BINANCE:{$signal->coin->symbol}"],
                    ]
                ]
            ];

            $response = $this->http->post(
                "https://api.telegram.org/bot{$this->botToken}/sendMessage",
                [
                    'json' => [
                        'chat_id'                  => $this->chatId,
                        'text'                     => $message,
                        'parse_mode'               => 'HTML',
                        'disable_web_page_preview' => false,
                        'reply_markup'             => $replyMarkup,
                    ],
                ]
            );

            $body = json_decode($response->getBody()->getContents(), true);

            if ($body['ok'] ?? false) {
                $signal->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);
                Log::info("Signal #{$signal->id} sent to Telegram successfully.");
                return true;
            } else {
                $this->markFailed($signal, $body['description'] ?? 'Unknown Telegram error');
                return false;
            }
        } catch (GuzzleException $e) {
            $this->markFailed($signal, $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim sinyal pending pilihan ke Telegram (VIP / High-Confidence Only & Rate Limited).
     */
    public function sendPending(): array
    {
        $results = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        if (!$this->isConfigured()) {
            Log::info("Telegram not configured — skipping notification.");
            $results['skipped'] = Signal::pending()->count();
            return $results;
        }

        // Filter Anti-Spam: Kirim HANYA sinyal VIP atau Confidence >= 70%, maksimal 2 sinyal terbaik per scan
        $signals = Signal::pending()
            ->where(function ($q) {
                $q->where('tier', 'VIP')->orWhere('confidence_score', '>=', 70);
            })
            ->with('coin')
            ->orderByDesc('confidence_score')
            ->take(2)
            ->get();

        foreach ($signals as $signal) {
            $success = $this->send($signal);
            $success ? $results['sent']++ : $results['failed']++;

            // Rate limit: 1 pesan per detik
            if ($signals->count() > 1) sleep(1);
        }

        return $results;
    }

    /**
     * Test koneksi Telegram Bot.
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Telegram not configured (token/chat_id kosong)'];
        }

        try {
            $response = $this->http->get(
                "https://api.telegram.org/bot{$this->botToken}/getMe"
            );
            $body = json_decode($response->getBody()->getContents(), true);

            if ($body['ok'] ?? false) {
                return [
                    'success' => true,
                    'bot_name'=> $body['result']['username'] ?? 'unknown',
                    'message' => 'Connected to Telegram Bot!',
                ];
            }
            return ['success' => false, 'message' => $body['description'] ?? 'Failed'];

        } catch (GuzzleException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->botToken) && !empty($this->chatId);
    }

    protected function markFailed(Signal $signal, string $reason): void
    {
        $signal->update([
            'status' => 'failed',
            'note'   => ($signal->note ? $signal->note . "\n" : '') . "Send failed: {$reason}",
        ]);
        Log::error("Signal #{$signal->id} failed to send: {$reason}");
    }
}
