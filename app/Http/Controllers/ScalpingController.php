<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\Indicator;
use App\Models\Signal;
use App\Services\TechnicalAnalysisService;
use Illuminate\Http\Request;

class ScalpingController extends Controller
{
    public function index(TechnicalAnalysisService $taService)
    {
        $coins = Coin::active()->with(['latestIndicator', 'latestNewsSentiment'])->get();
        $scalpCandidates = [];

        foreach ($coins as $coin) {
            $ind = $coin->latestIndicator;
            if (!$ind) continue;

            $analysis = $taService->analyze($coin, $ind);
            $direction = $analysis['direction'] ?? ($ind->is_above_ma20 ? 'LONG' : 'SHORT');

            // 15M Scalping ATR (Tight 1.0x ATR for SL & TP1)
            $price  = (float)($coin->last_price ?? 100);
            $rawAtr = (float)($ind->atr ?? 0);
            $atr    = ($rawAtr > 0 && $rawAtr <= ($price * 0.03)) ? $rawAtr : ($price * 0.012);

            $entry = $price;
            $risk  = $atr * 1.0; // Tight scalp stop loss

            if ($direction === 'LONG') {
                $sl  = max(0.00000001, $entry - $risk);
                $tp1 = $entry + ($risk * 1.0);  // Quick 1:1 scalp
                $tp2 = $entry + ($risk * 1.8);  // 1:1.8 scalp
                $tp3 = $entry + ($risk * 3.0);  // Extended scalp
            } else {
                $sl  = $entry + $risk;
                $tp1 = max(0.00000001, $entry - ($risk * 1.0));
                $tp2 = max(0.00000001, $entry - ($risk * 1.8));
                $tp3 = max(0.00000001, $entry - ($risk * 3.0));
            }

            // Build scalp drivers
            $drivers = [];
            if ($direction === 'LONG') {
                if ($ind->is_above_ema9 && $ind->is_above_ema21) $drivers[] = "EMA 9/21 Micro Golden Cross";
                if ($ind->is_macd_bullish) $drivers[] = "MACD 15m Bullish Impulse";
                if ($ind->is_rsi_healthy) $drivers[] = "RSI Momentum (" . round((float)$ind->rsi, 1) . ")";
                if ($ind->is_volume_spike) $drivers[] = "Volume Spike " . round((float)$ind->volume_spike, 1) . "x";
            } else {
                if (!$ind->is_above_ema9 && !$ind->is_above_ema21) $drivers[] = "EMA 9/21 Micro Death Cross";
                if (!$ind->is_macd_bullish) $drivers[] = "MACD 15m Bearish Pressure";
                if ((float)$ind->rsi < 45) $drivers[] = "RSI Bearish (" . round((float)$ind->rsi, 1) . ")";
                if ($ind->is_volume_spike) $drivers[] = "Volume Spike " . round((float)$ind->volume_spike, 1) . "x";
            }
            if (empty($drivers)) {
                $drivers = array_slice($analysis['reasons'], 0, 3);
            }

            $score = count($drivers) * 20 + ($analysis['score'] * 4);
            $score = min(98, max(40, $score));

            $candidateItem = [
                'coin'         => $coin,
                'direction'    => $direction,
                'score'        => $score,
                'last_price'   => $price,
                'entry'        => $entry,
                'sl'           => $sl,
                'tp1'          => $tp1,
                'tp2'          => $tp2,
                'tp3'          => $tp3,
                'drivers'      => array_slice($drivers, 0, 3),
                'is_low_price' => $price >= 0.01 && $price <= 10.00,
                'rsi'          => round((float)$ind->rsi, 1),
                'volume_spike' => round((float)$ind->volume_spike, 2),
            ];

            $scalpCandidates[] = $candidateItem;

            // Auto-persist high probability candidate into Signals table for Trail Testing
            if ($score >= 75) {
                Signal::firstOrCreate(
                    [
                        'coin_id'   => $coin->id,
                        'interval'  => '15m',
                        'direction' => $direction,
                        'status'    => 'pending',
                    ],
                    [
                        'entry_price'      => $entry,
                        'stop_loss'        => $sl,
                        'take_profit_1'    => $tp1,
                        'take_profit_2'    => $tp2,
                        'take_profit_3'    => $tp3,
                        'confidence_score' => $score,
                        'tier'             => 'VIP',
                    ]
                );
            }
        }

        usort($scalpCandidates, fn($a, $b) => $b['score'] <=> $a['score']);

        // Filter ONLY candidates with Momentum Score >= 75% (High Probability Scalps)
        $scalpCandidates = array_values(array_filter($scalpCandidates, fn($s) => $s['score'] >= 75));

        $recentScalpSignals = Signal::where('interval', '15m')->with('coin')->latest()->limit(10)->get();
        if ($recentScalpSignals->isEmpty()) {
            $recentScalpSignals = Signal::with('coin')->latest()->limit(10)->get();
        }

        // Stats summary
        $totalCandidates = count($scalpCandidates);
        $longCount       = collect($scalpCandidates)->where('direction', 'LONG')->count();
        $shortCount      = collect($scalpCandidates)->where('direction', 'SHORT')->count();
        $lowPriceCount   = collect($scalpCandidates)->where('is_low_price', true)->count();

        return view('scalping.index', compact(
            'scalpCandidates',
            'recentScalpSignals',
            'totalCandidates',
            'longCount',
            'shortCount',
            'lowPriceCount'
        ));
    }
}
