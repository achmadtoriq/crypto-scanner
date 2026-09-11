<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\Indicator;

/**
 * Stage 3: Screening & Filtering
 * Filter awal berdasarkan volume, price change, OI, spread, jenis koin, dan AI News Risk.
 */
class ScreeningService
{
    protected array $cfg;

    public function __construct(
        protected AiNewsSentimentService $newsService
    ) {
        $this->cfg = config('scanner.filter');
    }

    /**
     * Jalankan filter awal pada satu coin.
     * @return array ['pass' => bool, 'reason' => string]
     */
    public function screen(Coin $coin, Indicator $indicator): array
    {
        // 1. Exclude stablecoins
        if (in_array($coin->base_asset, $this->cfg['exclude_stablecoins'])) {
            return $this->fail("Stablecoin excluded: {$coin->base_asset}");
        }

        // 2. Volume 24h minimal
        if ((float)$coin->volume_24h < $this->cfg['min_volume_usdt']) {
            $vol = number_format((float)$coin->volume_24h / 1_000_000, 2);
            $min = number_format($this->cfg['min_volume_usdt'] / 1_000_000, 2);
            return $this->fail("Volume too low: {$vol}M < {$min}M USDT");
        }

        // 3. Price change minimal
        if (abs((float)$indicator->price_change_pct) < $this->cfg['min_price_change_pct']) {
            $chg = round((float)$indicator->price_change_pct, 2);
            return $this->fail("Price change too low: {$chg}%");
        }

        // 4. Spread maksimal
        if ((float)$indicator->spread_pct > $this->cfg['max_spread_pct']) {
            $sp = round((float)$indicator->spread_pct, 3);
            return $this->fail("Spread too wide: {$sp}%");
        }

        // 5. Open Interest
        if ($indicator->oi_change_pct !== null && (float)$indicator->oi_change_pct != 0) {
            if ((float)$indicator->oi_change_pct < $this->cfg['min_oi_change_pct']) {
                $oi = round((float)$indicator->oi_change_pct, 2);
                return $this->fail("OI change too low: {$oi}%");
            }
        }

        // 6. AI News Risk Check (Exclude Delisting / Regulatory High Risk)
        $newsRisk = $this->newsService->getCoinNewsRisk($coin);
        if ($newsRisk['has_risk']) {
            return $this->fail($newsRisk['reason']);
        }

        return $this->pass("Passed all filters. Vol: " . $coin->formatted_volume . " USDT");
    }

    /**
     * Screen multiple coins sekaligus, return hanya yang lolos.
     */
    public function screenAll(string $interval = '1h'): array
    {
        $results = ['passed' => [], 'failed' => []];

        $coins = Coin::active()
            ->with(['latestIndicator' => fn($q) => $q->where('interval', $interval)])
            ->get();

        foreach ($coins as $coin) {
            $indicator = $coin->latestIndicator;
            if (!$indicator) {
                $results['failed'][] = [
                    'coin'   => $coin,
                    'reason' => 'No indicator data available',
                ];
                continue;
            }

            $result = $this->screen($coin, $indicator);
            if ($result['pass']) {
                $results['passed'][] = ['coin' => $coin, 'indicator' => $indicator];
            } else {
                $results['failed'][] = ['coin' => $coin, 'reason' => $result['reason']];
            }
        }

        return $results;
    }

    protected function pass(string $message): array
    {
        return ['pass' => true, 'reason' => $message];
    }

    protected function fail(string $reason): array
    {
        return ['pass' => false, 'reason' => $reason];
    }
}
