<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'coin_id', 'stage', 'status', 'message', 'context', 'session_id',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function coin(): BelongsTo
    {
        return $this->belongsTo(Coin::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pass'  => 'green',
            'skip'  => 'yellow',
            'error' => 'red',
            'info'  => 'blue',
            default => 'gray',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'pass'  => '✅',
            'skip'  => '⏭️',
            'error' => '❌',
            'info'  => 'ℹ️',
            default => '•',
        };
    }
}
