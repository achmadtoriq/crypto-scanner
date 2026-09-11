<?php

namespace App\Http\Controllers;

use App\Models\Signal;
use App\Services\SignalTrackerService;
use Illuminate\Http\Request;

class SignalController extends Controller
{
    public function index(Request $request)
    {
        $query = Signal::with('coin')->latest();

        if ($request->direction) {
            $query->where('direction', $request->direction);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->outcome) {
            $query->where('outcome', $request->outcome);
        }
        if ($request->tier) {
            $query->where('tier', $request->tier);
        }
        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }
        if ($request->coin) {
            $query->whereHas('coin', fn($q) => $q->where('symbol', 'like', "%{$request->coin}%"));
        }
        if ($request->min_confidence) {
            $query->where('confidence_score', '>=', (int)$request->min_confidence);
        }

        $signals = $query->paginate(20)->withQueryString();

        return view('signals.index', compact('signals'));
    }

    public function exportCsv(Request $request)
    {
        $signals = Signal::with('coin')->latest()->get();
        $filename = 'crypto_signals_' . date('Y-m-d_H-i') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($signals) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Coin', 'Exchange', 'Direction', 'Tier', 'Entry Price', 'Stop Loss', 'TP1', 'TP2', 'TP3', 'Leverage', 'Confidence %', 'Outcome', 'PnL %', 'Status', 'Created At']);

            foreach ($signals as $s) {
                fputcsv($file, [
                    $s->id,
                    $s->coin->symbol ?? 'N/A',
                    strtoupper($s->coin->exchange ?? 'binance'),
                    $s->direction,
                    $s->tier,
                    $s->entry_price,
                    $s->stop_loss,
                    $s->take_profit_1,
                    $s->take_profit_2,
                    $s->take_profit_3,
                    $s->leverage . 'x',
                    $s->confidence_score . '%',
                    $s->outcome_label,
                    $s->pnl_pct ? $s->pnl_pct . '%' : 'N/A',
                    $s->status,
                    $s->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function show(Signal $signal, SignalTrackerService $tracker)
    {
        $signal->load('coin');

        // Refresh tracking for this signal
        $tracker->trackSignal($signal);
        $signal->refresh();

        return view('signals.show', compact('signal'));
    }

    public function destroy(Signal $signal)
    {
        $signal->delete();
        return redirect()->route('signals.index')->with('success', 'Signal deleted.');
    }

    public function cancel(Signal $signal)
    {
        $signal->update(['status' => 'cancelled']);
        return back()->with('success', 'Signal cancelled.');
    }

    public function reactivate(Signal $signal)
    {
        $signal->update(['status' => 'pending', 'outcome' => 'pending']);
        return back()->with('success', 'Signal reactivated! It is now pending and active for tracking.');
    }

    public function trackNow(SignalTrackerService $tracker)
    {
        $stats = $tracker->trackAllActiveSignals();
        return back()->with('success', "Signal tracking updated! Active: {$stats['total_active']}, Updated: {$stats['updated']}.");
    }
}
