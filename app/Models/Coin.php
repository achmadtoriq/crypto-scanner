<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Coin extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol', 'base_asset', 'quote_asset', 'exchange', 'type',
        'is_active', 'is_monitored', 'last_price', 'volume_24h', 'last_fetched_at',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'is_monitored'    => 'boolean',
        'last_price'      => 'decimal:8',
        'volume_24h'      => 'decimal:2',
        'last_fetched_at' => 'datetime',
    ];

    public function marketData(): HasMany
    {
        return $this->hasMany(MarketData::class);
    }

    public function latestMarketData(): HasOne
    {
        return $this->hasOne(MarketData::class)->latestOfMany('recorded_at');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class);
    }

    public function latestIndicator(): HasOne
    {
        return $this->hasOne(Indicator::class)->latestOfMany('calculated_at');
    }

    public function signals(): HasMany
    {
        return $this->hasMany(Signal::class);
    }

    public function latestSignal(): HasOne
    {
        return $this->hasOne(Signal::class)->latestOfMany();
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    public function newsSentiments(): HasMany
    {
        return $this->hasMany(NewsSentiment::class);
    }

    public function latestNewsSentiment(): HasOne
    {
        return $this->hasOne(NewsSentiment::class)->latestOfMany('published_at');
    }

    /** Format volume 24h yang lebih mudah dibaca */
    public function getFormattedVolumeAttribute(): string
    {
        $vol = (float) $this->volume_24h;
        if ($vol >= 1_000_000_000) return number_format($vol / 1_000_000_000, 2) . 'B';
        if ($vol >= 1_000_000)     return number_format($vol / 1_000_000, 2) . 'M';
        if ($vol >= 1_000)         return number_format($vol / 1_000, 2) . 'K';
        return number_format($vol, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_monitored', true);
    }
}
