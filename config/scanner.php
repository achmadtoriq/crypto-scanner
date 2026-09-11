<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Exchange API Settings
    |--------------------------------------------------------------------------
    */
    'binance_api_base' => env('BINANCE_API_BASE', 'https://api.binance.com'),
    'bybit_api_base'   => env('BYBIT_API_BASE', 'https://api.bybit.com'),
    'okx_api_base'     => env('OKX_API_BASE', 'https://www.okx.com'),

    /*
    |--------------------------------------------------------------------------
    | Telegram Notification
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
        'chat_id'   => env('TELEGRAM_CHAT_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scanner Parameters
    |--------------------------------------------------------------------------
    */
    'default_interval' => env('SCANNER_DEFAULT_INTERVAL', '1h'),

    // Stage 3: Filter Awal
    'filter' => [
        'min_volume_usdt'        => (float) env('SCANNER_MIN_VOLUME_USDT', 500_000),
        'min_price_change_pct'   => (float) env('SCANNER_MIN_PRICE_CHANGE_PCT', 0.2),
        'min_oi_change_pct'      => (float) env('SCANNER_MIN_OI_CHANGE_PCT', 0.5),
        'max_spread_pct'         => (float) env('SCANNER_MAX_SPREAD_PCT', 0.8),
        'exclude_stablecoins'    => ['USDC', 'BUSD', 'DAI', 'TUSD', 'USDP', 'FDUSD'],
    ],

    // Stage 4: Analisa Teknikal
    'analysis' => [
        'rsi_min'                => (float) env('SCANNER_RSI_MIN', 45),
        'rsi_max'                => (float) env('SCANNER_RSI_MAX', 75),
        'volume_spike_multiplier'=> (float) env('SCANNER_VOLUME_SPIKE_MULTIPLIER', 1.1),
        'ma_short'               => 20,
        'ma_long'                => 50,
        'atr_period'             => 14,
        'rsi_period'             => 14,
        'stoch_period'           => 14,
    ],

    // Stage 5 & 6: Signal + Risk
    'signal' => [
        'default_leverage'  => (int) env('SCANNER_DEFAULT_LEVERAGE', 10),
        'sl_atr_multiplier' => 2.0,   // v2: diperlebar dari 1.5 → 2.0 (tahan noise market)
        'tp1_rr'            => 1.5,
        'tp2_rr'            => 2.5,
        'tp3_rr'            => 4.0,
    ],

    'default_pairs' => [
        'BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'SOLUSDT', 'XRPUSDT',
        'DOGEUSDT', 'ADAUSDT', 'AVAXUSDT', 'DOTUSDT', 'MATICUSDT',
        'LINKUSDT', 'UNIUSDT', 'LTCUSDT', 'BCHUSDT', 'TRXUSDT',
        'NEARUSDT', 'APTUSDT', 'ARBUSDT', 'OPUSDT', 'INJUSDT',
        'SUIUSDT', 'SEIUSDT', 'TIAUSDT', 'WLDUSDT', 'FETUSDT',
        'RENDERUSDT', 'JUPUSDT', 'STRAXUSDT', 'TONUSDT', 'PEPEUSDT',
    ],
];
