<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsSentiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'coin_id', 'title', 'source', 'url',
        'sentiment_label', 'sentiment_score', 'risk_level',
        'ai_summary', 'catalyst', 'published_at',
    ];

    protected $casts = [
        'sentiment_score' => 'decimal:2',
        'published_at'    => 'datetime',
    ];

    public function coin(): BelongsTo
    {
        return $this->belongsTo(Coin::class);
    }

    public function getSentimentBadgeColorAttribute(): string
    {
        return match ($this->sentiment_label) {
            'bullish' => 'green',
            'bearish' => 'red',
            default   => 'blue',
        };
    }

    public function getSentimentEmojiAttribute(): string
    {
        return match ($this->sentiment_label) {
            'bullish' => '🟢 Bullish',
            'bearish' => '🔴 Bearish',
            default   => '⚪ Neutral',
        };
    }

    public function getRiskBadgeColorAttribute(): string
    {
        return match ($this->risk_level) {
            'high'   => 'red',
            'medium' => 'yellow',
            default  => 'green',
        };
    }
}
