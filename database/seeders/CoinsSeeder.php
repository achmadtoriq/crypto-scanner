<?php

namespace Database\Seeders;

use App\Models\Coin;
use Illuminate\Database\Seeder;

class CoinsSeeder extends Seeder
{
    public function run(): void
    {
        $binancePairs = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'BNBUSDT', 'XRPUSDT', 'PEPEUSDT', 'WLDUSDT', 'JUPUSDT', 'SUIUSDT', 'FETUSDT'];
        $bybitPairs   = ['TONUSDT', 'NEARUSDT', 'RENDERUSDT', 'SEIUSDT', 'INJUSDT'];
        $okxPairs     = ['APTUSDT', 'ARBUSDT', 'OPUSDT', 'TIAUSDT'];

        $count = 0;

        foreach ($binancePairs as $symbol) {
            $base = str_replace('USDT', '', $symbol);
            Coin::updateOrCreate(
                ['symbol' => $symbol],
                [
                    'base_asset'  => $base,
                    'quote_asset' => 'USDT',
                    'exchange'    => 'binance',
                    'type'        => 'perpetual',
                    'is_active'   => true,
                    'is_monitored'=> true,
                ]
            );
            $count++;
        }

        foreach ($bybitPairs as $symbol) {
            $base = str_replace('USDT', '', $symbol);
            Coin::updateOrCreate(
                ['symbol' => $symbol],
                [
                    'base_asset'  => $base,
                    'quote_asset' => 'USDT',
                    'exchange'    => 'bybit',
                    'type'        => 'perpetual',
                    'is_active'   => true,
                    'is_monitored'=> true,
                ]
            );
            $count++;
        }

        foreach ($okxPairs as $symbol) {
            $base = str_replace('USDT', '', $symbol);
            Coin::updateOrCreate(
                ['symbol' => $symbol],
                [
                    'base_asset'  => $base,
                    'quote_asset' => 'USDT',
                    'exchange'    => 'okx',
                    'type'        => 'perpetual',
                    'is_active'   => true,
                    'is_monitored'=> true,
                ]
            );
            $count++;
        }

        $this->command->info("✅ Seeded {$count} multi-exchange coins (Binance, Bybit, OKX)");
    }
}
