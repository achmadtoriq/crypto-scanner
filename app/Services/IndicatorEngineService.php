<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\Indicator;
use App\Models\MarketData;
use Illuminate\Support\Collection;

/**
 * Stage 2: Data Processing & Storage
 * Menghitung semua indikator teknikal dari data OHLCV yang sudah disimpan.
 */
class IndicatorEngineService
{
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = config('scanner.analysis');
    }

    /**
     * Hitung semua indikator untuk satu coin dan simpan ke DB.
     */
    public function calculate(Coin $coin, string $interval = '1h'): ?Indicator
    {
        $candles = MarketData::where('coin_id', $coin->id)
            ->where('interval', $interval)
            ->orderBy('recorded_at', 'desc')
            ->limit(60)
            ->get()
            ->sortBy('recorded_at')
            ->values();

        if ($candles->count() < 20) {
            return null;
        }

        $closes = $candles->pluck('close')->map(fn($v) => (float)$v)->toArray();
        $highs  = $candles->pluck('high')->map(fn($v) => (float)$v)->toArray();
        $lows   = $candles->pluck('low')->map(fn($v) => (float)$v)->toArray();
        $vols   = $candles->pluck('volume')->map(fn($v) => (float)$v)->toArray();

        $lastCandle   = $candles->last();
        $prevCandle   = $candles->get($candles->count() - 2);
        $currentClose = $closes[array_key_last($closes)];

        // Basic indicators
        $ma20        = $this->sma(array_slice($closes, -20), 20);
        $ma50        = count($closes) >= 50 ? $this->sma(array_slice($closes, -50), 50) : null;
        $ema9        = $this->ema($closes, 9);
        $ema21       = $this->ema($closes, 21);
        $rsi         = $this->rsi($closes, $this->cfg['rsi_period']);
        $atr         = $this->atr($highs, $lows, $closes, $this->cfg['atr_period']);
        $stoch       = $this->stochastic($closes, $highs, $lows, $this->cfg['stoch_period']);
        $avgVol20    = $this->sma(array_slice($vols, -20), 20);
        $volumeSpike = $avgVol20 > 0 ? (float)end($vols) / $avgVol20 : 0;

        // MACD (12, 26, 9)
        $macd = $this->macd($closes, 12, 26, 9);

        // Bollinger Bands (20, 2)
        $bb = $this->bollingerBands($closes, 20, 2.0);

        // Price change pct
        $prevClose      = $prevCandle ? (float)$prevCandle->close : $currentClose;
        $priceChangePct = $prevClose > 0 ? (($currentClose - $prevClose) / $prevClose) * 100 : 0;

        // OI change
        $oiChangePct = $this->calculateOiChange($coin, $interval);

        // Spread
        $spreadPct = $lastCandle->spread_pct ?? 0;

        // Flags
        $isAboveMa20    = $ma20 !== null && $currentClose > $ma20;
        $isAboveMa50    = $ma50 !== null && $currentClose > $ma50;
        $isAboveEma9    = $ema9 !== null && $currentClose > $ema9;
        $isAboveEma21   = $ema21 !== null && $currentClose > $ema21;
        $isVolSpike     = $volumeSpike >= $this->cfg['volume_spike_multiplier'];
        $isOiIncreasing = $oiChangePct > 0;
        $isRsiHealthy   = $rsi >= $this->cfg['rsi_min'] && $rsi <= $this->cfg['rsi_max'];
        $isMacdBullish  = $macd !== null && $macd['histogram'] > 0;
        $isBbSqueeze    = $bb !== null && $bb['pct_b'] >= 0 && $bb['width_pct'] < 2.0;

        return Indicator::updateOrCreate(
            ['coin_id' => $coin->id, 'interval' => $interval],
            [
                'price_change_pct' => round($priceChangePct, 4),
                'volume_spike'     => round($volumeSpike, 4),
                'oi_change_pct'    => round($oiChangePct, 4),
                'spread_pct'       => round($spreadPct, 6),
                'atr'              => $atr,
                'ma20'             => $ma20,
                'ma50'             => $ma50,
                'ema9'             => $ema9,
                'ema21'            => $ema21,
                'rsi'              => $rsi,
                'stoch_k'          => $stoch['k'] ?? null,
                'stoch_d'          => $stoch['d'] ?? null,
                'avg_volume_20'    => $avgVol20,
                'macd_line'        => $macd ? round($macd['macd'], 8) : null,
                'macd_signal'      => $macd ? round($macd['signal'], 8) : null,
                'macd_histogram'   => $macd ? round($macd['histogram'], 8) : null,
                'bb_upper'         => $bb ? round($bb['upper'], 8) : null,
                'bb_lower'         => $bb ? round($bb['lower'], 8) : null,
                'bb_pct_b'         => $bb ? round($bb['pct_b'], 4) : null,
                'is_above_ma20'    => $isAboveMa20,
                'is_above_ma50'    => $isAboveMa50,
                'is_above_ema9'    => $isAboveEma9,
                'is_above_ema21'   => $isAboveEma21,
                'is_volume_spike'  => $isVolSpike,
                'is_oi_increasing' => $isOiIncreasing,
                'is_rsi_healthy'   => $isRsiHealthy,
                'is_macd_bullish'  => $isMacdBullish,
                'is_bb_squeeze'    => $isBbSqueeze,
                'calculated_at'    => now(),
            ]
        );
    }

    // =========================================================
    // Private Indicator Calculations
    // =========================================================

    /** Simple Moving Average */
    protected function sma(array $values, int $period): ?float
    {
        if (count($values) < $period) return null;
        $slice = array_slice($values, -$period);
        return array_sum($slice) / $period;
    }

    /** Exponential Moving Average */
    protected function ema(array $closes, int $period): ?float
    {
        if (count($closes) < $period) return null;
        $k   = 2 / ($period + 1);
        $ema = array_sum(array_slice($closes, 0, $period)) / $period;
        foreach (array_slice($closes, $period) as $close) {
            $ema = ($close * $k) + ($ema * (1 - $k));
        }
        return round($ema, 8);
    }

    /** Full EMA array for MACD calculation */
    protected function emaArray(array $closes, int $period): array
    {
        if (count($closes) < $period) return [];
        $k      = 2 / ($period + 1);
        $ema    = array_sum(array_slice($closes, 0, $period)) / $period;
        $result = [$ema];
        foreach (array_slice($closes, $period) as $close) {
            $ema      = ($close * $k) + ($ema * (1 - $k));
            $result[] = $ema;
        }
        return $result;
    }

    /** MACD (12, 26, 9) */
    protected function macd(array $closes, int $fast = 12, int $slow = 26, int $signal = 9): ?array
    {
        if (count($closes) < $slow + $signal) return null;

        $ema12 = $this->emaArray($closes, $fast);
        $ema26 = $this->emaArray($closes, $slow);

        // MACD line: ema12 - ema26 (align lengths)
        $minLen    = min(count($ema12), count($ema26));
        $macdLine  = [];
        for ($i = 0; $i < $minLen; $i++) {
            $macdLine[] = $ema12[count($ema12) - $minLen + $i] - $ema26[count($ema26) - $minLen + $i];
        }

        if (count($macdLine) < $signal) return null;

        // Signal line: 9 EMA of MACD line
        $k          = 2 / ($signal + 1);
        $signalLine = array_sum(array_slice($macdLine, 0, $signal)) / $signal;
        foreach (array_slice($macdLine, $signal) as $v) {
            $signalLine = ($v * $k) + ($signalLine * (1 - $k));
        }

        $lastMacd      = end($macdLine);
        $histogram     = $lastMacd - $signalLine;

        return [
            'macd'      => $lastMacd,
            'signal'    => $signalLine,
            'histogram' => $histogram,
        ];
    }

    /** Bollinger Bands (20, 2) */
    protected function bollingerBands(array $closes, int $period = 20, float $stdDev = 2.0): ?array
    {
        if (count($closes) < $period) return null;

        $slice  = array_slice($closes, -$period);
        $middle = array_sum($slice) / $period;

        $variance = array_sum(array_map(fn($c) => pow($c - $middle, 2), $slice)) / $period;
        $std      = sqrt($variance);

        $upper  = $middle + ($stdDev * $std);
        $lower  = $middle - ($stdDev * $std);
        $last   = end($closes);

        // %B: where is price within the bands (0 = lower, 100 = upper)
        $pctB     = ($upper - $lower) > 0 ? (($last - $lower) / ($upper - $lower)) * 100 : 50;
        $widthPct = $middle > 0 ? (($upper - $lower) / $middle) * 100 : 0;

        return [
            'upper'     => $upper,
            'middle'    => $middle,
            'lower'     => $lower,
            'pct_b'     => $pctB,
            'width_pct' => $widthPct,
        ];
    }

    /** RSI - Relative Strength Index */
    protected function rsi(array $closes, int $period = 14): ?float
    {
        if (count($closes) <= $period) return null;

        $changes = [];
        for ($i = 1; $i < count($closes); $i++) {
            $changes[] = $closes[$i] - $closes[$i - 1];
        }

        $gains  = array_slice(array_map(fn($c) => max($c, 0), $changes), 0, $period);
        $losses = array_slice(array_map(fn($c) => max(-$c, 0), $changes), 0, $period);
        $avgGain = array_sum($gains) / $period;
        $avgLoss = array_sum($losses) / $period;

        foreach (array_slice($changes, $period) as $change) {
            $avgGain = ($avgGain * ($period - 1) + max($change, 0)) / $period;
            $avgLoss = ($avgLoss * ($period - 1) + max(-$change, 0)) / $period;
        }

        if ($avgLoss == 0) return 100.0;
        $rs = $avgGain / $avgLoss;
        return round(100 - (100 / (1 + $rs)), 4);
    }

    /** ATR - Average True Range */
    protected function atr(array $highs, array $lows, array $closes, int $period = 14): ?float
    {
        $n = count($closes);
        if ($n < $period + 1) return null;

        $trues = [];
        for ($i = 1; $i < $n; $i++) {
            $tr = max(
                $highs[$i] - $lows[$i],
                abs($highs[$i] - $closes[$i - 1]),
                abs($lows[$i] - $closes[$i - 1])
            );
            $trues[] = $tr;
        }

        if (count($trues) < $period) return null;

        $atr = array_sum(array_slice($trues, 0, $period)) / $period;
        foreach (array_slice($trues, $period) as $tr) {
            $atr = ($atr * ($period - 1) + $tr) / $period;
        }

        return round($atr, 8);
    }

    /** Stochastic Oscillator */
    protected function stochastic(array $closes, array $highs, array $lows, int $period = 14): array
    {
        $n = count($closes);
        if ($n < $period) return [];

        $kValues = [];
        for ($i = $period - 1; $i < $n; $i++) {
            $periodHighs = array_slice($highs, $i - $period + 1, $period);
            $periodLows  = array_slice($lows, $i - $period + 1, $period);
            $highest = max($periodHighs);
            $lowest  = min($periodLows);
            $range   = $highest - $lowest;
            $kValues[] = $range > 0 ? (($closes[$i] - $lowest) / $range) * 100 : 50;
        }

        $lastK   = end($kValues);
        $dValues = count($kValues) >= 3 ? array_slice($kValues, -3) : $kValues;
        $lastD   = array_sum($dValues) / count($dValues);

        return ['k' => round($lastK, 4), 'd' => round($lastD, 4)];
    }

    /** Hitung OI change dari data market_data */
    protected function calculateOiChange(Coin $coin, string $interval): float
    {
        $oiData = MarketData::where('coin_id', $coin->id)
            ->where('interval', $interval)
            ->whereNotNull('open_interest')
            ->orderBy('recorded_at', 'desc')
            ->limit(2)
            ->pluck('open_interest')
            ->toArray();

        if (count($oiData) < 2) return 0;
        $current  = (float)$oiData[0];
        $previous = (float)$oiData[1];
        if ($previous == 0) return 0;
        return (($current - $previous) / $previous) * 100;
    }
}
