<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\MarketData;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Stage 1: Data Ingestion
 * Mengambil data dari Binance Public API (tidak memerlukan API key).
 * Termasuk fallback generator data jika API tidak dapat dijangkau.
 */
class DataFetcherService
{
    protected Client $http;
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('scanner.binance_api_base', 'https://api.binance.com');
        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 5,
            'connect_timeout' => 3,
        ]);
    }

    /**
     * Ambil semua pair USDT dari Binance dan simpan ke DB.
     */
    public function fetchAndSyncPairs(): array
    {
        $defaultPairs = config('scanner.default_pairs', []);
        $synced = [];

        try {
            $response = $this->http->get('/api/v3/exchangeInfo');
            $data = json_decode($response->getBody()->getContents(), true);

            $activePairs = collect($data['symbols'] ?? [])
                ->where('status', 'TRADING')
                ->where('quoteAsset', 'USDT')
                ->whereIn('symbol', $defaultPairs)
                ->values();

            foreach ($activePairs as $pairData) {
                $coin = Coin::updateOrCreate(
                    ['symbol' => $pairData['symbol']],
                    [
                        'base_asset'  => $pairData['baseAsset'],
                        'quote_asset' => $pairData['quoteAsset'],
                        'exchange'    => 'binance',
                        'type'        => 'spot',
                        'is_active'   => true,
                    ]
                );
                $synced[] = $coin->symbol;
            }
        } catch (\Throwable $e) {
            Log::warning("Binance exchangeInfo fetch failed: {$e->getMessage()}. Using defaults.");
            $synced = $this->seedDefaultPairs($defaultPairs);
        }

        return $synced;
    }

    /**
     * Ambil ticker 24h untuk semua coin aktif.
     */
    public function fetch24hTickers(): array
    {
        $tickers = [];

        try {
            $response = $this->http->get('/api/v3/ticker/24hr');
            $data = json_decode($response->getBody()->getContents(), true);
            $tickerMap = collect($data)->keyBy('symbol');

            $coins = Coin::active()->get();
            foreach ($coins as $coin) {
                $ticker = $tickerMap->get($coin->symbol);
                if (!$ticker) continue;

                $coin->update([
                    'last_price'      => $ticker['lastPrice'],
                    'volume_24h'      => $ticker['quoteVolume'],
                    'last_fetched_at' => now(),
                ]);
                $tickers[$coin->symbol] = $ticker;
            }
        } catch (\Throwable $e) {
            Log::warning("Binance 24h ticker fetch failed: {$e->getMessage()}. Generating fallback tickers.");
            $tickers = $this->generateFallbackTickers();
        }

        return $tickers;
    }

    /**
     * Ambil data OHLCV (klines) untuk satu coin.
     */
    public function fetchKlines(string $symbol, string $interval = '1h', int $limit = 100): array
    {
        try {
            $response = $this->http->get('/api/v3/klines', [
                'query' => compact('symbol', 'interval', 'limit'),
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $e) {
            Log::warning("Klines fetch failed for {$symbol}: {$e->getMessage()}. Using synthetic klines.");
            return $this->generateSyntheticKlines($symbol, $interval, $limit);
        }
    }

    /**
     * Ambil orderbook (untuk spread).
     */
    public function fetchOrderBook(string $symbol, int $limit = 5): array
    {
        try {
            $response = $this->http->get('/api/v3/depth', [
                'query' => ['symbol' => $symbol, 'limit' => $limit],
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Proses fetch klines dan simpan ke market_data.
     */
    public function fetchAndStoreKlines(Coin $coin, string $interval = '1h', int $limit = 100): int
    {
        $klines = $this->fetchKlines($coin->symbol, $interval, $limit);
        if (empty($klines)) return 0;

        $book = $this->fetchOrderBook($coin->symbol, 5);
        $lastPrice = (float)($coin->last_price ?: 100);
        $bidPrice = isset($book['bids'][0][0]) ? $book['bids'][0][0] : $lastPrice * 0.9995;
        $askPrice = isset($book['asks'][0][0]) ? $book['asks'][0][0] : $lastPrice * 1.0005;

        $stored = 0;
        foreach ($klines as $k) {
            try {
                MarketData::updateOrCreate(
                    [
                        'coin_id'     => $coin->id,
                        'interval'    => $interval,
                        'recorded_at' => date('Y-m-d H:i:s', (int)($k[0] / 1000)),
                    ],
                    [
                        'open'        => $k[1],
                        'high'        => $k[2],
                        'low'         => $k[3],
                        'close'       => $k[4],
                        'volume'      => $k[5],
                        'quote_volume'=> $k[7],
                        'num_trades'  => $k[8] ?? 1000,
                        'bid_price'   => $bidPrice,
                        'ask_price'   => $askPrice,
                    ]
                );
                $stored++;
            } catch (\Throwable $e) {
                Log::warning("Store kline failed for {$coin->symbol}: {$e->getMessage()}");
            }
        }

        // Update coin last price from latest candle
        if (!empty($klines)) {
            $latestCandle = end($klines);
            $coin->update([
                'last_price'      => $latestCandle[4],
                'last_fetched_at' => now(),
            ]);
        }

        return $stored;
    }

    /**
     * Seed default pairs dari config jika API tidak tersedia.
     */
    protected function seedDefaultPairs(array $pairs): array
    {
        $synced = [];
        foreach ($pairs as $symbol) {
            $base = str_replace('USDT', '', $symbol);
            Coin::updateOrCreate(
                ['symbol' => $symbol],
                [
                    'base_asset'  => $base,
                    'quote_asset' => 'USDT',
                    'exchange'    => 'binance',
                    'type'        => 'spot',
                    'is_active'   => true,
                ]
            );
            $synced[] = $symbol;
        }
        return $synced;
    }

    /**
     * Generate fallback tickers when Binance API is unreachable.
     */
    protected function generateFallbackTickers(): array
    {
        $tickers = [];
        $coins = Coin::active()->get();
        
        $basePrices = [
            'BTCUSDT'    => 77270.00,
            'ETHUSDT'    => 2408.00,
            'BNBUSDT'    => 688.00,
            'SOLUSDT'    => 100.00,
            'XRPUSDT'    => 1.34,
            'DOGEUSDT'   => 0.081,
            'ADAUSDT'    => 0.1930,
            'AVAXUSDT'   => 7.24,
            'DOTUSDT'    => 0.88,
            'MATICUSDT'  => 0.09,
            'LINKUSDT'   => 11.22,
            'UNIUSDT'    => 6.35,
            'LTCUSDT'    => 49.40,
            'BCHUSDT'    => 248.00,
            'TRXUSDT'    => 0.32,
            'NEARUSDT'   => 1.88,
            'APTUSDT'    => 0.61,
            'ARBUSDT'    => 0.11,
            'OPUSDT'     => 0.10,
            'INJUSDT'    => 4.7870,
            'SUIUSDT'    => 0.73,
            'SEIUSDT'    => 0.047,
            'TIAUSDT'    => 0.33,
            'WLDUSDT'    => 0.37,
            'FETUSDT'    => 0.15,
            'RENDERUSDT' => 1.50,
            'JUPUSDT'    => 0.22,
            'STRAXUSDT'  => 0.0095,
            'TONUSDT'    => 1.32,
            'PEPEUSDT'   => 0.0000089,
        ];

        foreach ($coins as $coin) {
            // PRESERVE current last_price if already set (e.g. synced live from browser)
            $basePrice = ((float)$coin->last_price > 0) ? (float)$coin->last_price : ($basePrices[$coin->symbol] ?? rand(5, 100));
            $vol       = (float)$coin->volume_24h > 0 ? (float)$coin->volume_24h : rand(5, 50) * 1_000_000;
            $coin->update([
                'last_price'      => $basePrice,
                'volume_24h'      => $vol,
                'last_fetched_at' => now(),
            ]);
            $tickers[$coin->symbol] = [
                'symbol'      => $coin->symbol,
                'lastPrice'   => $basePrice,
                'quoteVolume' => $vol
            ];
        }

        return $tickers;
    }

    /**
     * Generate realistic synthetic OHLCV klines for testing when offline.
     * Guarantees latest candle close equals exact real market price without drift.
     */
    protected function generateSyntheticKlines(string $symbol, string $interval = '1h', int $limit = 60): array
    {
        $basePrices = [
            'BTCUSDT'    => 77270.00,
            'ETHUSDT'    => 2408.00,
            'BNBUSDT'    => 688.00,
            'SOLUSDT'    => 100.00,
            'XRPUSDT'    => 1.34,
            'DOGEUSDT'   => 0.081,
            'ADAUSDT'    => 0.1930,
            'AVAXUSDT'   => 7.24,
            'DOTUSDT'    => 0.88,
            'MATICUSDT'  => 0.09,
            'LINKUSDT'   => 11.22,
            'UNIUSDT'    => 6.35,
            'LTCUSDT'    => 49.40,
            'BCHUSDT'    => 248.00,
            'TRXUSDT'    => 0.32,
            'NEARUSDT'   => 1.88,
            'APTUSDT'    => 0.61,
            'ARBUSDT'    => 0.11,
            'OPUSDT'     => 0.10,
            'INJUSDT'    => 4.7870,
            'SUIUSDT'    => 0.73,
            'SEIUSDT'    => 0.047,
            'TIAUSDT'    => 0.33,
            'WLDUSDT'    => 0.37,
            'FETUSDT'    => 0.15,
            'RENDERUSDT' => 1.50,
            'JUPUSDT'    => 0.22,
            'STRAXUSDT'  => 0.0095,
            'TONUSDT'    => 1.32,
            'PEPEUSDT'   => 0.0000089,
        ];

        $targetPrice = $basePrices[$symbol] ?? (crc32($symbol) % 100 + 5);
        $coin = Coin::where('symbol', $symbol)->first();
        if ($coin && (float)$coin->last_price > 0) {
            $targetPrice = (float)$coin->last_price;
        }

        $now = time();
        $intervalSec = $interval === '1h' ? 3600 : ($interval === '4h' ? 14400 : 86400);

        // Generate prices backwards from targetPrice
        $closes = array_fill(0, $limit, $targetPrice);
        $curr = $targetPrice;
        for ($i = $limit - 1; $i >= 0; $i--) {
            $closes[$i] = $curr;
            $volatility = (rand(-10, 10) / 1000);
            $curr = $curr / (1 + $volatility);
        }

        $klines = [];
        for ($i = 0; $i < $limit; $i++) {
            $candleTime = ($now - (($limit - 1 - $i) * $intervalSec)) * 1000;
            $close = $closes[$i];
            $open  = $i > 0 ? $closes[$i - 1] : $close * 0.998;
            $high  = max($open, $close) * 1.002;
            $low   = min($open, $close) * 0.998;

            $baseVol = ($close < 1) ? 500000 : 50;
            $vol = $baseVol * (rand(10, 30) / 10);
            $quoteVol = $vol * $close;

            $klines[] = [
                $candleTime,
                number_format($open, 8, '.', ''),
                number_format($high, 8, '.', ''),
                number_format($low, 8, '.', ''),
                number_format($close, 8, '.', ''),
                number_format($vol, 8, '.', ''),
                $candleTime + $intervalSec * 1000 - 1,
                number_format($quoteVol, 2, '.', ''),
                rand(500, 3000)
            ];
        }

        return $klines;
    }
}
