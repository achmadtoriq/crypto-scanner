<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Indicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'coin_id', 'interval',
        'price_change_pct', 'volume_spike', 'oi_change_pct', 'spread_pct', 'atr',
        'ma20', 'ma50', 'rsi', 'stoch_k', 'stoch_d', 'avg_volume_20',
        'is_above_ma20', 'is_above_ma50', 'is_volume_spike',
        'is_oi_increasing', 'is_rsi_healthy', 'calculated_at',
        'ema9', 'ema21', 'macd_line', 'macd_signal', 'macd_histogram',
        'bb_upper', 'bb_lower', 'bb_pct_b', 'is_macd_bullish',
        'is_above_ema9', 'is_above_ema21', 'is_bb_squeeze',
    ];

    protected $casts = [
        'price_change_pct' => 'decimal:4',
        'volume_spike'     => 'decimal:4',
        'oi_change_pct'    => 'decimal:4',
        'spread_pct'       => 'decimal:6',
        'atr'              => 'decimal:8',
        'ma20'             => 'decimal:8',
        'ma50'             => 'decimal:8',
        'ema9'             => 'decimal:8',
        'ema21'            => 'decimal:8',
        'macd_line'        => 'decimal:8',
        'macd_signal'      => 'decimal:8',
        'macd_histogram'   => 'decimal:8',
        'bb_upper'         => 'decimal:8',
        'bb_lower'         => 'decimal:8',
        'bb_pct_b'         => 'decimal:4',
        'rsi'              => 'decimal:4',
        'stoch_k'          => 'decimal:4',
        'stoch_d'          => 'decimal:4',
        'avg_volume_20'    => 'decimal:8',
        'is_above_ma20'    => 'boolean',
        'is_above_ma50'    => 'boolean',
        'is_volume_spike'  => 'boolean',
        'is_oi_increasing' => 'boolean',
        'is_rsi_healthy'   => 'boolean',
        'is_macd_bullish'  => 'boolean',
        'is_above_ema9'    => 'boolean',
        'is_above_ema21'   => 'boolean',
        'is_bb_squeeze'    => 'boolean',
        'calculated_at'    => 'datetime',
    ];

    public function coin(): BelongsTo
    {
        return $this->belongsTo(Coin::class);
    }
}
