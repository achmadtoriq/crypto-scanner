<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Filter group
            ['key' => 'filter.min_volume_usdt',      'value' => '1000000',  'group' => 'filter', 'label' => 'Min Volume 24h (USDT)',       'type' => 'number',  'description' => 'Minimum volume 24 jam dalam USDT'],
            ['key' => 'filter.min_price_change_pct', 'value' => '1.0',      'group' => 'filter', 'label' => 'Min Price Change (%)',         'type' => 'number',  'description' => 'Minimum perubahan harga dalam persen'],
            ['key' => 'filter.min_oi_change_pct',    'value' => '2.0',      'group' => 'filter', 'label' => 'Min OI Change (%)',            'type' => 'number',  'description' => 'Minimum perubahan Open Interest'],
            ['key' => 'filter.max_spread_pct',       'value' => '0.5',      'group' => 'filter', 'label' => 'Max Spread (%)',               'type' => 'number',  'description' => 'Maksimum spread bid-ask dalam persen'],

            // Analysis group
            ['key' => 'analysis.rsi_min',            'value' => '50',       'group' => 'analysis', 'label' => 'RSI Minimum',               'type' => 'number',  'description' => 'RSI minimum untuk sinyal LONG (momentum sehat)'],
            ['key' => 'analysis.rsi_max',            'value' => '70',       'group' => 'analysis', 'label' => 'RSI Maximum',               'type' => 'number',  'description' => 'RSI maximum sebelum overbought'],
            ['key' => 'analysis.volume_spike_mult',  'value' => '1.5',      'group' => 'analysis', 'label' => 'Volume Spike Multiplier',   'type' => 'number',  'description' => 'Multiplier volume vs rata-rata 20 candle'],

            // Signal group
            ['key' => 'signal.default_leverage',     'value' => '10',       'group' => 'signal',   'label' => 'Default Leverage',          'type' => 'number',  'description' => 'Leverage default untuk sinyal (1-125x)'],
            ['key' => 'signal.sl_atr_multiplier',    'value' => '1.5',      'group' => 'signal',   'label' => 'SL ATR Multiplier',         'type' => 'number',  'description' => 'Stop loss = entry ± (ATR × multiplier)'],

            // Telegram group
            ['key' => 'telegram.bot_token',          'value' => '',         'group' => 'telegram', 'label' => 'Telegram Bot Token',        'type' => 'text',    'description' => 'Token dari @BotFather'],
            ['key' => 'telegram.chat_id',            'value' => '',         'group' => 'telegram', 'label' => 'Telegram Chat ID',          'type' => 'text',    'description' => 'ID channel/grup tujuan sinyal'],

            // General
            ['key' => 'scanner.interval',            'value' => '1h',       'group' => 'general',  'label' => 'Default Interval',          'type' => 'text',    'description' => 'Timeframe default scanner (1h, 4h, 1d)'],
            ['key' => 'scanner.auto_run',            'value' => 'false',    'group' => 'general',  'label' => 'Auto Run Scheduler',        'type' => 'boolean', 'description' => 'Jalankan scanner otomatis via scheduler'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info("✅ Seeded " . count($settings) . " settings");
    }
}
