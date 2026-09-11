<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Services\BacktestService;
use Illuminate\Http\Request;

class BacktestController extends Controller
{
    public function __construct(protected BacktestService $backtestService)
    {
    }

    public function index()
    {
        $coins = Coin::active()->orderBy('symbol')->get();
        return view('backtest.index', compact('coins'));
    }

    public function run(Request $request)
    {
        $validated = $request->validate([
            'filter_type' => 'required|string|in:all,under_10,under_1,coin',
            'symbol'      => 'nullable|string',
            'days'        => 'required|integer|in:7,14,30,90',
            'interval'    => 'required|string|in:15m,1h,4h',
        ]);

        $results = $this->backtestService->runBacktest($validated);

        return response()->json($results);
    }
}
