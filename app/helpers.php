<?php

if (!function_exists('fmtPrice')) {
    /**
     * Format a crypto price with dynamic precision.
     * - $100+    → 2 decimal places (e.g. BTC: $77,874.01)
     * - $1–$100  → 4 decimal places (e.g. SOL: $102.1500)
     * - $0.001–$1 → 6 decimal places (e.g. XRP: $0.809300)
     * - <$0.001  → 8 decimal places (e.g. PEPE: $0.00001120)
     */
    function fmtPrice(float|string|null $val, bool $withDollar = false): string
    {
        $v = (float) $val;
        if ($v >= 100)    $decimals = 2;
        elseif ($v >= 1)  $decimals = 4;
        elseif ($v >= 0.001) $decimals = 6;
        else $decimals = 8;

        $formatted = number_format($v, $decimals);
        return $withDollar ? '$' . $formatted : $formatted;
    }
}
