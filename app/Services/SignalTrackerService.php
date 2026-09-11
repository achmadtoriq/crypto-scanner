<?php

namespace App\Services;

use App\Models\MarketData;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

/**
 * SignalTrackerService — Forward Testing & Outcome Tracking Engine
 * Memantau secara otomatis pergerakan harga terbaru koin vs Entry, SL, dan TP1-3.
 */
class SignalTrackerService
{
    /**
     * Lakukan tracking pada semua sinyal yang masih aktif (outcome = 'pending').
     */
    public function trackAllActiveSignals(): array
    {
        $activeSignals = Signal::activeOutcome()->with('coin')->get();
        $stats = [
            'total_active' => $activeSignals->count(),
            'updated'      => 0,
            'hit_tp'       => 0,
            'hit_sl'       => 0,
            'expired'      => 0,
        ];

        foreach ($activeSignals as $signal) {
            $result = $this->trackSignal($signal);
            if ($result['updated']) {
                $stats['updated']++;
                if (str_contains($result['outcome'], 'hit_tp')) $stats['hit_tp']++;
                if ($result['outcome'] === 'hit_sl')           $stats['hit_sl']++;
                if ($result['outcome'] === 'expired')          $stats['expired']++;
            }
        }

        return $stats;
    }

    /**
     * Tracking satu sinyal spesifik.
     */
    public function trackSignal(Signal $signal): array
    {
        if ($signal->outcome !== 'pending') {
            return ['updated' => false, 'outcome' => $signal->outcome];
        }

        // Ambil data klines terbaru sejak sinyal dibuat
        $recentCandles = MarketData::where('coin_id', $signal->coin_id)
            ->where('recorded_at', '>=', $signal->created_at->subMinutes(5))
            ->get();

        if ($recentCandles->isEmpty()) {
            // Jika belum ada candle baru, gunakan last_price koin
            $lastPrice = (float)($signal->coin->last_price ?? $signal->entry_price);
            $highest = $lastPrice;
            $lowest  = $lastPrice;
        } else {
            $highest = (float)$recentCandles->max('high');
            $lowest  = (float)$recentCandles->min('low');
        }

        // Update tracking ekstrim harga
        $currentHighest = max((float)($signal->highest_price_reached ?? $signal->entry_price), $highest);
        $currentLowest  = min((float)($signal->lowest_price_reached  ?? $signal->entry_price), $lowest);

        $signal->highest_price_reached = $currentHighest;
        $signal->lowest_price_reached  = $currentLowest;

        $direction  = $signal->direction;
        $entry      = (float)$signal->entry_price;
        $sl         = (float)$signal->stop_loss;
        $tp1        = (float)$signal->take_profit_1;
        $tp2        = (float)$signal->take_profit_2;
        $tp3        = (float)$signal->take_profit_3;
        $leverage   = $signal->leverage ?: 10;

        $newOutcome = 'pending';
        $pnlPct     = null;

        if ($direction === 'LONG') {
            if ($currentHighest >= $tp3) {
                $newOutcome = 'hit_tp3';
                $pnlPct     = (($tp3 - $entry) / $entry) * 100 * $leverage;
            } elseif ($currentHighest >= $tp2) {
                // If price retraces back below TP1 after hitting TP2, close with TP2 profit
                $newOutcome = 'hit_tp2';
                $pnlPct     = (($tp2 - $entry) / $entry) * 100 * $leverage;
            } elseif ($currentHighest >= $tp1) {
                // Trailing SL at Entry: if price drops below entry after TP1, close as HIT_TP1 (Break-Even)
                if ($currentLowest <= $entry) {
                    $newOutcome = 'hit_tp1';
                    $pnlPct     = (($tp1 - $entry) / $entry) * 100 * $leverage;
                }
            } elseif ($currentLowest <= $sl) {
                $newOutcome = 'hit_sl';
                $pnlPct     = (($sl - $entry) / $entry) * 100 * $leverage; // negative
            }
        } else {
            // SHORT
            if ($currentLowest <= $tp3) {
                $newOutcome = 'hit_tp3';
                $pnlPct     = (($entry - $tp3) / $entry) * 100 * $leverage;
            } elseif ($currentLowest <= $tp2) {
                $newOutcome = 'hit_tp2';
                $pnlPct     = (($entry - $tp2) / $entry) * 100 * $leverage;
            } elseif ($currentLowest <= $tp1) {
                if ($currentHighest >= $entry) {
                    $newOutcome = 'hit_tp1';
                    $pnlPct     = (($entry - $tp1) / $entry) * 100 * $leverage;
                }
            } elseif ($currentHighest >= $sl) {
                $newOutcome = 'hit_sl';
                $pnlPct     = (($entry - $sl) / $entry) * 100 * $leverage; // negative
            }
        }

        // Cek jika sinyal sudah kadaluarsa (> 48 jam tanpa tersentuh TP/SL)
        if ($newOutcome === 'pending' && $signal->created_at->diffInHours(now()) >= 48) {
            $newOutcome = 'expired';
            $pnlPct     = 0;
        }

        if ($newOutcome !== 'pending') {
            $signal->outcome   = $newOutcome;
            $signal->pnl_pct   = round($pnlPct, 2);
            $signal->closed_at = now();
            $signal->save();

            // Auto-sync related order execution status & exact target outcome marker
            $exactOutcome = strtoupper($newOutcome);
            $orderStatus  = match(true) {
                str_contains($newOutcome, 'hit_tp') => 'CLOSED',
                $newOutcome === 'hit_sl' => 'STOPPED',
                default => 'CANCELLED',
            };

            \App\Models\Order::where('signal_id', $signal->id)->update([
                'status'  => $orderStatus,
                'outcome' => $exactOutcome,
            ]);

            Log::info("Signal #{$signal->id} ({$signal->coin->symbol}) outcome updated to: {$newOutcome} (PnL: {$pnlPct}%)");
            return ['updated' => true, 'outcome' => $newOutcome, 'pnl' => $pnlPct];
        } else {
            // If signal is still pending (in-trade, heading towards TP1/2/3), ensure order is marked as FILLED (In Trade)
            \App\Models\Order::where('signal_id', $signal->id)
                ->where('outcome', '!=', 'HIT_TP1')
                ->where('outcome', '!=', 'HIT_TP2')
                ->where('outcome', '!=', 'HIT_TP3')
                ->where('outcome', '!=', 'HIT_SL')
                ->update(['status' => 'FILLED', 'outcome' => 'FILLED']);
        }

        $signal->save();
        return ['updated' => false, 'outcome' => 'pending'];
    }
}
