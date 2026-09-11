<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketData extends Model
{
    use HasFactory;

    protected $table = 'market_data';

    protected $fillable = [
        'coin_id', 'interval', 'open', 'high', 'low', 'close', 'volume',
        'quote_volume', 'num_trades', 'open_interest', 'funding_rate',
        'bid_price', 'ask_price', 'recorded_at',
    ];

    protected $casts = [
        'open'          => 'decimal:8',
        'high'          => 'decimal:8',
        'low'           => 'decimal:8',
        'close'         => 'decimal:8',
        'volume'        => 'decimal:8',
        'quote_volume'  => 'decimal:2',
        'open_interest' => 'decimal:8',
        'funding_rate'  => 'decimal:8',
        'bid_price'     => 'decimal:8',
        'ask_price'     => 'decimal:8',
        'recorded_at'   => 'datetime',
    ];

    public function coin(): BelongsTo
    {
        return $this->belongsTo(Coin::class);
    }

    /** Hitung spread dalam persen */
    public function getSpreadPctAttribute(): float
    {
        if (!$this->bid_price || !$this->ask_price || (float)$this->bid_price == 0) {
            return 0;
        }
        return ((float)$this->ask_price - (float)$this->bid_price) / (float)$this->bid_price * 100;
    }

    public function scopeInterval($query, string $interval)
    {
        return $query->where('interval', $interval);
    }
}
