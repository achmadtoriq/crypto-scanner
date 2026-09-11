<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\Indicator;

/**
 * Stage 4: Technical Analysis
 * Analisa breakout, support/resistance, MA trend, RSI, Stochastic, MACD, Bollinger Bands,
 * EMA Crossover, dan Multi-Timeframe alignment.
 */
class TechnicalAnalysisService
{
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = config('scanner.analysis');
    }

    /**
     * Jalankan analisa teknikal lengkap pada satu coin.
     */
    public function analyze(Coin $coin, Indicator $indicator): array
    {
        $score      = 0;
        $reasons    = [];
        $longScore  = 0;
        $shortScore = 0;

        // 1. Volume Spike
        if ($indicator->is_volume_spike) {
            $spike = round((float)$indicator->volume_spike, 2);
            $reasons[] = "✅ Volume spike: {$spike}x avg (breakout confirmation)";
            $score++;
        } else {
            $reasons[] = "⚠️ Volume: " . round((float)$indicator->volume_spike, 2) . "x avg";
        }

        // 2. MA Trend (MA20 & MA50)
        if ($indicator->is_above_ma20 && $indicator->is_above_ma50) {
            $reasons[] = "✅ Price above MA20 & MA50 → Bullish trend";
            $longScore += 2;
            $score++;
        } elseif (!$indicator->is_above_ma20 && !$indicator->is_above_ma50) {
            $reasons[] = "✅ Price below MA20 & MA50 → Bearish trend";
            $shortScore += 2;
            $score++;
        } else {
            $reasons[] = "⚠️ MA20/MA50 trend mixed";
        }

        // 3. EMA 9/21 Crossover (Golden/Death Cross)
        if ($indicator->is_above_ema9 && $indicator->is_above_ema21) {
            if ($indicator->ema9 !== null && $indicator->ema21 !== null
                && (float)$indicator->ema9 > (float)$indicator->ema21) {
                $reasons[] = "✅ EMA9 > EMA21 → Golden Cross bullish";
                $longScore += 2;
                $score++;
            }
        } elseif (!$indicator->is_above_ema9 && !$indicator->is_above_ema21) {
            if ($indicator->ema9 !== null && $indicator->ema21 !== null
                && (float)$indicator->ema9 < (float)$indicator->ema21) {
                $reasons[] = "✅ EMA9 < EMA21 → Death Cross bearish";
                $shortScore += 2;
                $score++;
            }
        }

        // 4. MACD Crossover
        if ($indicator->macd_line !== null && $indicator->macd_signal !== null) {
            $macdLine = (float)$indicator->macd_line;
            $macdSig  = (float)$indicator->macd_signal;
            $macdHist = (float)($indicator->macd_histogram ?? 0);

            if ($indicator->is_macd_bullish && $macdHist > 0) {
                $reasons[] = "✅ MACD bullish crossover (histogram: +" . round($macdHist, 6) . ")";
                $longScore += 2;
                $score++;
            } elseif (!$indicator->is_macd_bullish && $macdHist < 0) {
                $reasons[] = "✅ MACD bearish crossover (histogram: " . round($macdHist, 6) . ")";
                $shortScore += 2;
                $score++;
            } else {
                $reasons[] = "⚠️ MACD neutral (hist: " . round($macdHist, 6) . ")";
            }
        }

        // 5. RSI Zone
        $rsi = (float)$indicator->rsi;
        if ($indicator->is_rsi_healthy) {
            $reasons[] = "✅ RSI healthy: " . round($rsi, 1) . " (50-70 bullish zone)";
            $longScore++;
            $score++;
        } elseif ($rsi > 30 && $rsi < 50) {
            $reasons[] = "⚠️ RSI bearish: " . round($rsi, 1);
            $shortScore++;
        } elseif ($rsi >= 70) {
            $reasons[] = "⚠️ RSI overbought: " . round($rsi, 1);
        } elseif ($rsi <= 30) {
            $reasons[] = "⚠️ RSI oversold (reversal potential): " . round($rsi, 1);
            $shortScore++;
        }

        // 6. Bollinger Band Squeeze (breakout imminent)
        if ($indicator->is_bb_squeeze) {
            $reasons[] = "✅ Bollinger Band squeeze → Volatility breakout imminent";
            $score++;
        }
        if ($indicator->bb_pct_b !== null) {
            $pctB = (float)$indicator->bb_pct_b;
            if ($pctB > 80) {
                $reasons[] = "⚠️ Price near Upper Band (%B=" . round($pctB,1) . ")";
            } elseif ($pctB < 20) {
                $reasons[] = "⚠️ Price near Lower Band (%B=" . round($pctB,1) . ") - Oversold";
            }
        }

        // 7. Stochastic
        if ($indicator->stoch_k !== null) {
            $k = (float)$indicator->stoch_k;
            $d = (float)$indicator->stoch_d;
            if ($k > $d && $k > 50 && $k < 80) {
                $reasons[] = "✅ Stoch K({$k}) > D({$d}) → Bullish momentum";
                $longScore++;
                $score++;
            } elseif ($k < $d && $k < 50 && $k > 20) {
                $reasons[] = "✅ Stoch K({$k}) < D({$d}) → Bearish momentum";
                $shortScore++;
                $score++;
            }
        }

        // 8. OI Increasing
        if ($indicator->is_oi_increasing) {
            $oi = round((float)$indicator->oi_change_pct, 2);
            $reasons[] = "✅ OI +{$oi}% → Institutional interest";
            $score++;
        }

        // 9. Price Change Momentum
        $priceChg = (float)$indicator->price_change_pct;
        if ($priceChg > 0) {
            $longScore++;
            $reasons[] = "📈 Price momentum: +" . round($priceChg, 2) . "%";
        } elseif ($priceChg < 0) {
            $shortScore++;
            $reasons[] = "📉 Price momentum: " . round($priceChg, 2) . "%";
        }

        // 10. Multi-Timeframe Alignment (4h)
        $htfIndicator = Indicator::where('coin_id', $coin->id)
            ->where('interval', '4h')
            ->latest('calculated_at')
            ->first();

        if ($htfIndicator) {
            if ($longScore > $shortScore && $htfIndicator->is_above_ma20) {
                $score++;
                $reasons[] = "🌐 MTF 4h: Bullish aligned (confirmed)";
            } elseif ($shortScore > $longScore && !$htfIndicator->is_above_ma20) {
                $score++;
                $reasons[] = "🌐 MTF 4h: Bearish aligned (confirmed)";
            }
        }

        $direction = null;
        if ($longScore > $shortScore) {
            $direction = 'LONG';
        } elseif ($shortScore > $longScore) {
            $direction = 'SHORT';
        }

        // v2 Fix 2: Naikkan minimum score dari 2 → 3 (filter sinyal noise)
        $valid = $score >= 3 && $direction !== null;

        // v2 Fix 3: MTF Gate — tolak sinyal yang berlawanan dengan trend 4h
        if ($valid && $htfIndicator) {
            if ($direction === 'LONG' && !$htfIndicator->is_above_ma20) {
                $valid = false;
                $reasons[] = "🚫 MTF Gate: LONG ditolak — 4h masih BEARISH";
            } elseif ($direction === 'SHORT' && $htfIndicator->is_above_ma20) {
                $valid = false;
                $reasons[] = "🚫 MTF Gate: SHORT ditolak — 4h masih BULLISH";
            }
        }

        // v3 Upgrade: BTC Market Context Gate — tolak sinyal LONG Altcoin jika BTC sedang dumping/bearish
        if ($valid && $direction === 'LONG' && $coin->symbol !== 'BTCUSDT') {
            $btcCoin = Coin::where('symbol', 'BTCUSDT')->first();
            if ($btcCoin) {
                $btcInd = Indicator::where('coin_id', $btcCoin->id)->where('interval', '1h')->latest('calculated_at')->first();
                if ($btcInd && ($btcInd->price_change_pct < -1.5 || !$btcInd->is_above_ma20)) {
                    $valid = false;
                    $reasons[] = "🚫 BTC Gate: LONG Altcoin ditolak — BTC sedang dumping (" . round($btcInd->price_change_pct, 2) . "%)";
                }
            }
        }

        $tier  = $score >= 5 ? 'VIP' : 'STANDARD';

        return [
            'valid'       => $valid,
            'direction'   => $direction,
            'score'       => $score,
            'tier'        => $tier,
            'long_score'  => $longScore,
            'short_score' => $shortScore,
            'reasons'     => $reasons,
        ];
    }

    /**
     * Hitung & ambil koin paling potensial dengan opportunity score.
     */
    public function getTopPotentialCoins(int $limit = 4): array
    {
        $coins = Coin::active()->with(['latestIndicator', 'latestNewsSentiment', 'signals' => fn($q) => $q->latest()])->get();
        $potentialList = [];

        foreach ($coins as $coin) {
            $ind = $coin->latestIndicator;
            if (!$ind) continue;

            $news = $coin->latestNewsSentiment;
            $oppScore = 0;
            $analysis  = $this->analyze($coin, $ind);
            $direction = $analysis['direction'] ?? ($ind->is_above_ma20 ? 'LONG' : 'SHORT');
            $drivers   = [];

            if ($direction === 'LONG') {
                if ($ind->is_above_ma20 && $ind->is_above_ma50) $drivers[] = "MA Trend Bullish";
                if ($ind->is_rsi_healthy) $drivers[] = "RSI Ideal (" . round((float)$ind->rsi, 1) . ")";
                if ($ind->is_macd_bullish) $drivers[] = "MACD Bullish Crossover";
                if ($ind->ema9 && $ind->ema21 && (float)$ind->ema9 > (float)$ind->ema21) $drivers[] = "EMA9/21 Golden Cross";
                if ($ind->is_volume_spike) $drivers[] = "Vol Spike " . round((float)$ind->volume_spike, 1) . "x";
                if ($news && $news->sentiment_label === 'bullish') $drivers[] = "AI Bullish Catalyst";
            } else {
                if (!$ind->is_above_ma20 && !$ind->is_above_ma50) $drivers[] = "MA Trend Bearish";
                if ((float)$ind->rsi < 45) $drivers[] = "RSI Bearish (" . round((float)$ind->rsi, 1) . ")";
                if (!$ind->is_macd_bullish) $drivers[] = "MACD Bearish Crossover";
                if ($ind->ema9 && $ind->ema21 && (float)$ind->ema9 < (float)$ind->ema21) $drivers[] = "EMA9/21 Death Cross";
                if ($ind->is_volume_spike) $drivers[] = "Vol Spike " . round((float)$ind->volume_spike, 1) . "x";
                if ($news && $news->sentiment_label === 'bearish') $drivers[] = "AI Bearish Catalyst";
            }

            if (empty($drivers)) {
                $drivers = array_slice($analysis['reasons'], 0, 3);
            }

            $oppScore = count($drivers) * 15 + ($analysis['score'] * 5);
            if ($direction === 'LONG' && $ind->is_above_ma20) $oppScore += 10;
            if ($direction === 'SHORT' && !$ind->is_above_ma20) $oppScore += 10;
            $oppScore = min(98, max(25, $oppScore));
            $price     = (float)($coin->last_price ?? 100);
            $rawAtr    = (float)($ind->atr ?? 0);
            $atr       = ($rawAtr > 0 && $rawAtr <= ($price * 0.05)) ? $rawAtr : ($price * 0.02);

            $entry = $price;
            $risk  = $atr * 1.5;

            if ($direction === 'LONG') {
                $sl  = max(0.00000001, $entry - $risk);
                $tp1 = $entry + ($risk * 1.5);
                $tp2 = $entry + ($risk * 2.5);
                $tp3 = $entry + ($risk * 4.0);
            } else {
                $sl  = $entry + $risk;
                $tp1 = max(0.00000001, $entry - ($risk * 1.5));
                $tp2 = max(0.00000001, $entry - ($risk * 2.5));
                $tp3 = max(0.00000001, $entry - ($risk * 4.0));
            }

            $potentialList[] = [
                'coin'       => $coin,
                'score'      => min(98, $oppScore),
                'direction'  => $direction,
                'drivers'    => array_slice($drivers, 0, 3),
                'last_price' => $price,
                'volume_24h' => $coin->formatted_volume,
                'rsi'        => round((float)$ind->rsi, 1),
                'macd_bull'  => $ind->is_macd_bullish,
                'entry'      => $entry,
                'sl'         => $sl,
                'tp1'        => $tp1,
                'tp2'          => $tp2,
                'tp3'          => $tp3,
                'is_low_price' => $price >= 0.01 && $price <= 10.00,
            ];
        }

        usort($potentialList, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($potentialList, 0, $limit);
    }
}
