<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\Indicator;
use App\Models\Signal;

/**
 * Stage 5 & 6: Signal Generation + Risk & Position Setup
 * Generate sinyal trading dengan entry, SL, TP, leverage, VIP tiering, dan confidence score.
 */
class SignalGeneratorService
{
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = config('scanner.signal');
    }

    public function generate(Coin $coin, Indicator $indicator, array $analysisResult): ?Signal
    {
        if (!$analysisResult['valid'] || !$analysisResult['direction']) {
            return null;
        }

        // Anti-Spam / Cooldown Protection: Skip if an active signal for this coin exists in last 4 hours
        $recentActive = Signal::where('coin_id', $coin->id)
            ->where('outcome', 'pending')
            ->where('created_at', '>=', now()->subHours(4))
            ->exists();

        if ($recentActive) {
            return null;
        }

        $direction   = $analysisResult['direction'];
        $tier        = $analysisResult['tier'] ?? 'STANDARD';
        $entryPrice  = (float)$coin->last_price;
        $rawAtr      = (float)$indicator->atr;

        if ($entryPrice <= 0) {
            return null;
        }

        // Normalize ATR to a realistic percentage of entry price (max 5.0% of price)
        $atr = ($rawAtr > 0 && $rawAtr <= ($entryPrice * 0.05)) ? $rawAtr : ($entryPrice * 0.02);

        // =============================================
        // Stage 6: Risk & Position Calculation
        // =============================================
        $slMultiplier = $this->cfg['sl_atr_multiplier'];
        $risk         = $atr * $slMultiplier;

        if ($direction === 'LONG') {
            $stopLoss      = max(0.00000001, $entryPrice - $risk);
            $takeProfit1   = $entryPrice + ($risk * $this->cfg['tp1_rr']);
            $takeProfit2   = $entryPrice + ($risk * $this->cfg['tp2_rr']);
            $takeProfit3   = $entryPrice + ($risk * $this->cfg['tp3_rr']);
        } else {
            // SHORT
            $stopLoss      = $entryPrice + $risk;
            $takeProfit1   = max(0.00000001, $entryPrice - ($risk * $this->cfg['tp1_rr']));
            $takeProfit2   = max(0.00000001, $entryPrice - ($risk * $this->cfg['tp2_rr']));
            $takeProfit3   = max(0.00000001, $entryPrice - ($risk * $this->cfg['tp3_rr']));
        }

        if ($stopLoss <= 0 || $takeProfit1 <= 0) {
            return null;
        }

        $leverage = $this->cfg['default_leverage'];

        // Confidence Score (0–100)
        $maxScore        = 6;
        $confidenceScore = min(100, round(($analysisResult['score'] / $maxScore) * 100, 2));

        if ($analysisResult['long_score'] >= 4 || $analysisResult['short_score'] >= 4) {
            $confidenceScore = min(100, $confidenceScore + 10);
        }

        $message = $this->formatTelegramMessage(
            $coin, $direction, $entryPrice, $stopLoss,
            $takeProfit1, $takeProfit2, $takeProfit3,
            $leverage, $confidenceScore, $tier
        );

        $chartUrl = "https://www.tradingview.com/chart/?symbol=BINANCE:{$coin->symbol}&interval=60";

        return Signal::create([
            'coin_id'                => $coin->id,
            'direction'              => $direction,
            'tier'                   => $tier,
            'outcome'                => 'pending',
            'entry_price'            => round($entryPrice, 8),
            'stop_loss'              => round($stopLoss, 8),
            'take_profit_1'          => round($takeProfit1, 8),
            'take_profit_2'          => round($takeProfit2, 8),
            'take_profit_3'          => round($takeProfit3, 8),
            'highest_price_reached'  => round($entryPrice, 8),
            'lowest_price_reached'   => round($entryPrice, 8),
            'leverage'               => $leverage,
            'confidence_score'       => $confidenceScore,
            'status'                 => 'pending',
            'telegram_message'       => $message,
            'chart_url'              => $chartUrl,
            'note'                   => implode("\n", $analysisResult['reasons']),
            'interval'               => config('scanner.default_interval', '1h'),
            'atr_at_signal'          => $atr,
            'rsi_at_signal'          => $indicator->rsi,
            'volume_spike_at_signal' => $indicator->volume_spike,
        ]);
    }

    protected function formatTelegramMessage(
        Coin $coin,
        string $direction,
        float $entry,
        float $sl,
        float $tp1,
        float $tp2,
        float $tp3,
        int $leverage,
        float $confidence,
        string $tier
    ): string {
        $emoji     = $direction === 'LONG' ? '🟢' : '🔴';
        $dirLabel  = $direction === 'LONG' ? 'LONG' : 'SHORT';
        $tierBadge = $tier === 'VIP' ? '⭐ VIP SIGNAL' : '📡 SIGNAL SCANNER';
        $base      = $coin->base_asset;
        $chartUrl  = "https://www.tradingview.com/chart/?symbol=BINANCE:{$coin->symbol}&interval=60";
        $now       = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i') . ' WIB';
        $prec      = $this->getPrecision($entry);

        return <<<MSG
        {$tierBadge}
        {$emoji} {$base} / USDT ({$dirLabel})

        🎯 Entry: {$this->fmt($entry, $prec)}
        ✅ TP1: {$this->fmt($tp1, $prec)} (RR 1:1.5)
        ✅ TP2: {$this->fmt($tp2, $prec)} (RR 1:2.5)
        ✅ TP3: {$this->fmt($tp3, $prec)}+ (RR 1:4.0)
        🛑 SL: {$this->fmt($sl, $prec)}
        ⚡ Leverage: {$leverage}x (Cross)
        📊 Confidence: {$confidence}%
        ⏰ Time: {$now}
        📈 Chart: {$chartUrl}

        ⚠️ Bukan saran investasi. Kelola risiko dengan bijak.
        MSG;
    }

    protected function fmt(float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', '');
    }

    protected function getPrecision(float $price): int
    {
        if ($price >= 1000) return 2;
        if ($price >= 100)  return 3;
        if ($price >= 1)    return 4;
        if ($price >= 0.01) return 6;
        return 8;
    }
}
