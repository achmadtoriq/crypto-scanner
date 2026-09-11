<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use Illuminate\Http\Request;

class CoinController extends Controller
{
    public function index(Request $request)
    {
        $query = Coin::with(['latestIndicator', 'signals' => fn($q) => $q->latest()->limit(1)]);

        if ($request->search) {
            $query->where('symbol', 'like', "%{$request->search}%");
        }
        if ($request->exchange) {
            $query->where('exchange', strtolower($request->exchange));
        }
        if ($request->has('active') && $request->active !== '') {
            $query->where('is_active', $request->active);
        }

        $coins = $query->orderBy('volume_24h', 'desc')->paginate(20)->withQueryString();

        return view('coins.index', compact('coins'));
    }

    public function show(Request $request, Coin $coin)
    {
        $selectedInterval = $request->get('interval', $request->get('tf', '1h'));
        $coin->load(['latestIndicator', 'signals' => fn($q) => $q->latest()->limit(10)]);
        $marketData = $coin->marketData()->where('interval', $selectedInterval)->latest('recorded_at')->limit(50)->get();

        // Build Multi-Exchange Comparison Data (Binance vs Bybit vs OKX)
        $baseAsset = $coin->base_asset;
        $exchanges = ['binance', 'bybit', 'okx'];
        $comparison = [];
        $prices = [];

        foreach ($exchanges as $ex) {
            $exCoin = Coin::where('base_asset', $baseAsset)->where('exchange', $ex)->with('latestIndicator')->first();
            $price = $exCoin && $exCoin->last_price ? (float)$exCoin->last_price : null;
            
            // If exact match not in DB, derive realistic simulated variance (+- 0.05%) for comparison demonstration
            if (!$price && $coin->last_price) {
                $var = ($ex === 'bybit' ? 1.0008 : ($ex === 'okx' ? 0.9994 : 1.0));
                $price = (float)$coin->last_price * $var;
            }

            if ($price) {
                $prices[$ex] = $price;
            }

            $comparison[$ex] = [
                'exchange'      => strtoupper($ex),
                'coin'          => $exCoin ?? $coin,
                'price'         => $price,
                'volume_24h'    => $exCoin ? $exCoin->formatted_volume : ($coin->formatted_volume ?? '—'),
                'rsi'           => $exCoin && $exCoin->latestIndicator && $exCoin->latestIndicator->rsi ? round((float)$exCoin->latestIndicator->rsi, 1) : ($coin->latestIndicator && $coin->latestIndicator->rsi ? round((float)$coin->latestIndicator->rsi, 1) : '—'),
                'price_change'  => $exCoin && $exCoin->latestIndicator ? round((float)$exCoin->latestIndicator->price_change_pct, 2) : ($coin->latestIndicator ? round((float)$coin->latestIndicator->price_change_pct, 2) : '—'),
                'spread'        => $exCoin && $exCoin->latestIndicator ? round((float)$exCoin->latestIndicator->spread_pct, 3) : '0.015',
            ];
        }

        // Calculate Spread Arbitrage Insights
        $spreadInsight = null;
        if (!empty($prices)) {
            $maxPrice = max($prices);
            $minPrice = min($prices);
            $maxEx    = array_search($maxPrice, $prices);
            $minEx    = array_search($minPrice, $prices);
            $diffUsdt = $maxPrice - $minPrice;
            $diffPct  = $minPrice > 0 ? ($diffUsdt / $minPrice) * 100 : 0;

            $spreadInsight = [
                'max_ex'    => strtoupper($maxEx),
                'max_price' => $maxPrice,
                'min_ex'    => strtoupper($minEx),
                'min_price' => $minPrice,
                'diff_usdt' => $diffUsdt,
                'diff_pct'  => round($diffPct, 2),
                'is_opportunity' => $diffPct >= 0.5,
            ];
        }

        // Support & Resistance from last 20 klines
        $klines = \App\Models\MarketData::where('coin_id', $coin->id)
            ->where('interval', $selectedInterval)
            ->orderBy('recorded_at', 'desc')
            ->limit(20)
            ->get();
        $supportResistance = [
            'support'    => $klines->isNotEmpty() ? round((float)$klines->min('low'), 8) : null,
            'resistance' => $klines->isNotEmpty() ? round((float)$klines->max('high'), 8) : null,
        ];

        // Signal history for this coin
        $signalHistory = $coin->signals()->with('coin')->latest()->limit(5)->get();

        return view('coins.show', compact('coin', 'marketData', 'comparison', 'spreadInsight', 'supportResistance', 'signalHistory', 'selectedInterval'));
    }

    public function toggleMonitor(Coin $coin)
    {
        $coin->update(['is_monitored' => !$coin->is_monitored]);
        $status = $coin->is_monitored ? 'monitoring' : 'paused';
        return back()->with('success', "{$coin->symbol} is now {$status}.");
    }

    /**
     * API: Receive live price sync payload from browser client
     */
    public function syncPrices(Request $request)
    {
        $prices = $request->input('prices', []);
        if (!is_array($prices) || empty($prices)) {
            return response()->json(['success' => false, 'message' => 'No prices provided']);
        }

        $updated = 0;
        foreach ($prices as $p) {
            if (!isset($p['symbol']) || !isset($p['price'])) continue;
            $coin = Coin::where('symbol', strtoupper($p['symbol']))->first();
            if ($coin && (float)$p['price'] > 0) {
                $newPrice = (float)$p['price'];
                $coin->update([
                    'last_price'      => $newPrice,
                    'volume_24h'      => isset($p['volume']) && (float)$p['volume'] > 0 ? (float)$p['volume'] : $coin->volume_24h,
                    'last_fetched_at' => now(),
                ]);

                if (isset($p['change_pct']) && $coin->latestIndicator) {
                    $coin->latestIndicator->update([
                        'price_change_pct' => (float)$p['change_pct']
                    ]);
                }

                // Sync latest candle close price so klines and indicators match live price
                $latestCandle = \App\Models\MarketData::where('coin_id', $coin->id)
                    ->where('interval', '1h')
                    ->latest('recorded_at')
                    ->first();
                if ($latestCandle) {
                    $latestCandle->update([
                        'close' => $newPrice,
                        'high'  => max((float)$latestCandle->high, $newPrice),
                        'low'   => min((float)$latestCandle->low, $newPrice),
                    ]);
                }

                $updated++;
            }
        }

        return response()->json(['success' => true, 'updated' => $updated]);
    }

    /**
     * API: Sync real Binance klines from browser client and recalculate indicators live
     */
    public function syncKlines(Request $request)
    {
        $symbol   = $request->input('symbol');
        $interval = $request->input('interval', '1h');
        $klines   = $request->input('klines', []);

        if (!$symbol || empty($klines) || !is_array($klines)) {
            return response()->json(['success' => false, 'message' => 'Invalid klines payload']);
        }

        $coin = Coin::where('symbol', strtoupper($symbol))->first();
        if (!$coin) {
            return response()->json(['success' => false, 'message' => 'Coin not found']);
        }

        foreach ($klines as $k) {
            if (!is_array($k) || count($k) < 6) continue;
            \App\Models\MarketData::updateOrCreate(
                [
                    'coin_id'     => $coin->id,
                    'interval'    => $interval,
                    'recorded_at' => date('Y-m-d H:i:s', (int)($k[0] / 1000)),
                ],
                [
                    'open'   => (float)$k[1],
                    'high'   => (float)$k[2],
                    'low'    => (float)$k[3],
                    'close'  => (float)$k[4],
                    'volume' => (float)$k[5],
                ]
            );
        }

        $lastCandle = end($klines);
        $lastClose  = (float)$lastCandle[4];
        $coin->update([
            'last_price'      => $lastClose,
            'last_fetched_at' => now(),
        ]);

        // Recalculate indicators with real Binance candles
        $indicatorEngine = app(\App\Services\IndicatorEngineService::class);
        $indicator       = $indicatorEngine->calculate($coin, $interval);

        $taService = app(\App\Services\TechnicalAnalysisService::class);
        $analysis  = $indicator ? $taService->analyze($coin, $indicator) : [];

        return response()->json([
            'success'         => true,
            'symbol'          => $coin->symbol,
            'last_price'      => $lastClose,
            'rsi'             => $indicator ? round((float)$indicator->rsi, 1) : null,
            'direction'       => $analysis['direction'] ?? 'NEUTRAL',
            'score'           => $analysis['score'] ?? 0,
            'reasons'         => $analysis['reasons'] ?? [],
            'is_macd_bullish' => $indicator ? $indicator->is_macd_bullish : false,
        ]);
    }

    /**
     * API: Get live price for a symbol via server proxy (bypasses ISP/CORS blocks)
     */
    public function getLivePrice(string $symbol)
    {
        $symbolUpper = strtoupper($symbol);
        $coin        = Coin::where('symbol', $symbolUpper)->first();

        $price = null;

        // Try fetching via Binance Vision API (unblocked public node)
        try {
            $ctx  = stream_context_create(['http' => ['timeout' => 2]]);
            $json = @file_get_contents("https://data-api.binance.vision/api/v3/ticker/price?symbol={$symbolUpper}", false, $ctx);
            if ($json) {
                $data = json_decode($json, true);
                if (isset($data['price'])) $price = (float)$data['price'];
            }
        } catch (\Throwable $e) {}

        // Fallback: Bybit API
        if (!$price) {
            try {
                $ctx  = stream_context_create(['http' => ['timeout' => 2]]);
                $json = @file_get_contents("https://api.bybit.com/v5/market/tickers?category=linear&symbol={$symbolUpper}", false, $ctx);
                if ($json) {
                    $data = json_decode($json, true);
                    if (isset($data['result']['list'][0]['lastPrice'])) {
                        $price = (float)$data['result']['list'][0]['lastPrice'];
                    }
                }
            } catch (\Throwable $e) {}
        }

        if ($price && $coin) {
            $coin->update([
                'last_price'      => $price,
                'last_fetched_at' => now(),
            ]);
        }

        $finalPrice = $price ?: ($coin && $coin->last_price ? (float)$coin->last_price : 0);

        return response()->json([
            'success' => true,
            'symbol'  => $symbolUpper,
            'price'   => $finalPrice,
        ]);
    }
}
