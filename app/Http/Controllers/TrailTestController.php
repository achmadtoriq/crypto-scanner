<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\MarketData;
use App\Models\Signal;
use Illuminate\Http\Request;

class TrailTestController extends Controller
{
    public function index(Request $request)
    {
        $selectedInterval = $request->get('interval', 'all');
        $selectedStatus   = $request->get('status', 'all');

        $query = Signal::with('coin')->latest('created_at');

        if ($selectedInterval !== 'all') {
            $query->where('interval', $selectedInterval);
        }

        if ($selectedStatus === 'tp_hit') {
            $query->whereIn('status', ['HIT_TP1', 'HIT_TP2', 'HIT_TP3']);
        } elseif ($selectedStatus === 'sl_hit') {
            $query->where('status', 'HIT_SL');
        } elseif ($selectedStatus === 'pending') {
            $query->where('status', 'PENDING');
        }

        $signals = $query->paginate(25);

        // Build Trail Test Data for each signal matching exact user spreadsheet layout
        $trailTests = [];
        foreach ($signals as $sig) {
            $coin = $sig->coin;
            if (!$coin) continue;

            $entryP = (float)$sig->entry_price;
            $slP    = (float)$sig->stop_loss;
            $tp1P   = (float)($sig->take_profit_1 ?? $sig->tp1);
            $tp2P   = (float)($sig->take_profit_2 ?? $sig->tp2);
            $tp3P   = (float)($sig->take_profit_3 ?? $sig->tp3);
            $isLong = $sig->direction === 'LONG';

            // Find candle when price touched entry level
            $entryCandle = MarketData::where('coin_id', $coin->id)
                ->where('interval', $sig->interval)
                ->where('recorded_at', '>=', $sig->created_at)
                ->where(function($q) use ($entryP) {
                    $q->where('low', '<=', $entryP)->where('high', '>=', $entryP);
                })
                ->orderBy('recorded_at', 'asc')
                ->first();

            $entryDatetime = $entryCandle ? $entryCandle->recorded_at->format('Y-m-d H:i') : ($sig->entry_hit_at ? $sig->entry_hit_at->format('Y-m-d H:i') : $sig->created_at->format('Y-m-d H:i'));

            // Check Trail Test outcomes for SL, TP1, TP2, TP3
            $slStatus  = ['hit' => false, 'label' => '[ok]', 'price' => $slP, 'color' => 'var(--green)'];
            $tp1Status = ['hit' => false, 'label' => '[x]',  'price' => $tp1P, 'color' => 'var(--text-muted)'];
            $tp2Status = ['hit' => false, 'label' => '[x]',  'price' => $tp2P, 'color' => 'var(--text-muted)'];
            $tp3Status = ['hit' => false, 'label' => '[x]',  'price' => $tp3P, 'color' => 'var(--text-muted)'];

            if ($sig->status === 'HIT_SL' || ($sig->exit_reason === 'SL_HIT')) {
                $slStatus = ['hit' => true, 'label' => '[x]', 'price' => $sig->exit_price ?? $slP, 'color' => 'var(--red)'];
            }

            if ($sig->hit_tp1_at || in_array($sig->status, ['HIT_TP1', 'HIT_TP2', 'HIT_TP3'])) {
                $tp1Status = ['hit' => true, 'label' => '[ok]', 'price' => $tp1P, 'color' => 'var(--green)'];
            }
            if ($sig->hit_tp2_at || in_array($sig->status, ['HIT_TP2', 'HIT_TP3'])) {
                $tp2Status = ['hit' => true, 'label' => '[ok]', 'price' => $tp2P, 'color' => 'var(--green)'];
            }
            if ($sig->hit_tp3_at || $sig->status === 'HIT_TP3') {
                $tp3Status = ['hit' => true, 'label' => '[ok]', 'price' => $tp3P, 'color' => 'var(--green)'];
            }

            // Fallback checking against MarketData if not explicitly tagged
            if (!$slStatus['hit'] && !$tp1Status['hit']) {
                $subsequentCandles = MarketData::where('coin_id', $coin->id)
                    ->where('interval', $sig->interval)
                    ->where('recorded_at', '>=', $sig->created_at)
                    ->orderBy('recorded_at', 'asc')
                    ->get();

                foreach ($subsequentCandles as $c) {
                    $high = (float)$c->high;
                    $low  = (float)$c->low;

                    if ($isLong) {
                        if ($high >= $tp1P) $tp1Status = ['hit' => true, 'label' => '[ok]', 'price' => $tp1P, 'color' => 'var(--green)'];
                        if ($high >= $tp2P) $tp2Status = ['hit' => true, 'label' => '[ok]', 'price' => $tp2P, 'color' => 'var(--green)'];
                        if ($high >= $tp3P) $tp3Status = ['hit' => true, 'label' => '[ok]', 'price' => $tp3P, 'color' => 'var(--green)'];
                        if ($low <= $slP && !$tp1Status['hit']) {
                            $slStatus = ['hit' => true, 'label' => '[x]', 'price' => $slP, 'color' => 'var(--red)'];
                            break;
                        }
                    } else {
                        if ($low <= $tp1P) $tp1Status = ['hit' => true, 'label' => '[ok]', 'price' => $tp1P, 'color' => 'var(--green)'];
                        if ($low <= $tp2P) $tp2Status = ['hit' => true, 'label' => '[ok]', 'price' => $tp2P, 'color' => 'var(--green)'];
                        if ($low <= $tp3P) $tp3Status = ['hit' => true, 'label' => '[ok]', 'price' => $tp3P, 'color' => 'var(--green)'];
                        if ($high >= $slP && !$tp1Status['hit']) {
                            $slStatus = ['hit' => true, 'label' => '[x]', 'price' => $slP, 'color' => 'var(--red)'];
                            break;
                        }
                    }
                }
            }

            // Calculate End Date 15m (Exit timestamp or +15m candle close)
            if ($sig->closed_at) {
                $endDatetime = $sig->closed_at->format('Y-m-d H:i');
            } elseif ($sig->hit_tp1_at) {
                $endDatetime = $sig->hit_tp1_at->format('Y-m-d H:i');
            } elseif ($entryCandle && $entryCandle->recorded_at) {
                $endDatetime = $entryCandle->recorded_at->copy()->addMinutes(15)->format('Y-m-d H:i');
            } else {
                $endDatetime = $sig->created_at->copy()->addMinutes(15)->format('Y-m-d H:i');
            }

            $trailTests[] = [
                'signal'         => $sig,
                'coin_name'      => $coin->base_asset . '/USDT',
                'direction'      => $sig->direction,
                'entry'          => $entryP,
                'sl'             => $slP,
                'tp1'            => $tp1P,
                'tp2'            => $tp2P,
                'tp3'            => $tp3P,
                'datetime'       => $entryDatetime,
                'end_datetime'   => $endDatetime,
                'timeframe'      => $sig->interval ?? '15m',
                'sl_outcome'     => $slStatus,
                'tp1_outcome'    => $tp1Status,
                'tp2_outcome'    => $tp2Status,
                'tp3_outcome'    => $tp3Status,
            ];
        }

        // Summary Stats
        $totalTests = count($trailTests);
        $tp1Hits    = collect($trailTests)->filter(fn($t) => $t['tp1_outcome']['hit'])->count();
        $tp2Hits    = collect($trailTests)->filter(fn($t) => $t['tp2_outcome']['hit'])->count();
        $tp3Hits    = collect($trailTests)->filter(fn($t) => $t['tp3_outcome']['hit'])->count();
        $slHits     = collect($trailTests)->filter(fn($t) => $t['sl_outcome']['hit'])->count();
        $winRate    = $totalTests > 0 ? round(($tp1Hits / $totalTests) * 100, 1) : 0;

        return view('trail-test.index', compact(
            'signals',
            'trailTests',
            'totalTests',
            'tp1Hits',
            'tp2Hits',
            'tp3Hits',
            'slHits',
            'winRate',
            'selectedInterval',
            'selectedStatus'
        ));
    }

    public function exportCsv(Request $request)
    {
        $signals = Signal::with('coin')->latest('created_at')->get();

        $filename = 'trail_test_report_' . date('Y-m-d_H-i') . '.csv';
        $headers  = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($signals) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Recomended Potensial - Koin',
                'Recomended Potensial - Live Price (Tick)',
                'Recomended Potensial - Entry',
                'Recomended Potensial - SL',
                'Recomended Potensial - TP1',
                'Recomended Potensial - TP2',
                'Recomended Potensial - TP3',
                'Datetime (waktu price sama dengan entry)',
                'End Date 15m (waktu TP/SL/Candle close)',
                'Timeframe',
                'Trail Test - SL [x/ok] Price',
                'Trail Test - TP1 [ok/x] Price',
                'Trail Test - TP2 [ok/x] Price',
                'Trail Test - TP3 [ok/x] Price',
            ]);

            foreach ($signals as $sig) {
                $coin   = $sig->coin;
                $symbol = $coin ? $coin->base_asset . '/USDT' : $sig->symbol;
                $lastP  = $coin && $coin->last_price ? $coin->last_price : $sig->entry_price;
                $tp1Ok  = in_array($sig->status, ['HIT_TP1', 'HIT_TP2', 'HIT_TP3']) ? '[ok]' : '[x]';
                $tp2Ok  = in_array($sig->status, ['HIT_TP2', 'HIT_TP3']) ? '[ok]' : '[x]';
                $tp3Ok  = $sig->status === 'HIT_TP3' ? '[ok]' : '[x]';
                $slOk   = $sig->status === 'HIT_SL' ? '[x]' : '[ok]';

                $tp1Val = $sig->take_profit_1 ?? $sig->tp1;
                $tp2Val = $sig->take_profit_2 ?? $sig->tp2;
                $tp3Val = $sig->take_profit_3 ?? $sig->tp3;
                $endDt  = $sig->closed_at ? $sig->closed_at->format('Y-m-d H:i') : $sig->created_at->copy()->addMinutes(15)->format('Y-m-d H:i');

                fputcsv($file, [
                    $symbol . ' (' . $sig->direction . ')',
                    $lastP,
                    $sig->entry_price,
                    $sig->stop_loss,
                    $tp1Val,
                    $tp2Val,
                    $tp3Val,
                    $sig->created_at->format('Y-m-d H:i'),
                    $endDt,
                    $sig->interval ?? '15m',
                    "{$slOk} " . $sig->stop_loss,
                    "{$tp1Ok} " . $tp1Val,
                    "{$tp2Ok} " . $tp2Val,
                    "{$tp3Ok} " . $tp3Val,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
