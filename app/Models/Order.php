<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'signal_id',
        'coin_id',
        'symbol',
        'direction',
        'exchange',
        'order_type',
        'entry_price',
        'stop_loss',
        'take_profit',
        'margin_usd',
        'position_size_usd',
        'leverage',
        'status',
        'outcome',
    ];

    protected $casts = [
        'entry_price'       => 'float',
        'stop_loss'         => 'float',
        'take_profit'       => 'float',
        'margin_usd'        => 'float',
        'position_size_usd' => 'float',
        'leverage'          => 'integer',
        'created_at'        => 'datetime',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public function coin()
    {
        return $this->belongsTo(Coin::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'EXECUTED' => 'badge-blue',
            'FILLED'   => 'badge-purple',
            'CLOSED'   => 'badge-pass',
            'STOPPED'  => 'badge-short',
            'CANCELLED'=> 'badge-short',
            default    => 'badge-pending',
        };
    }

    public function getOutcomeBadgeAttribute(): string
    {
        return match ($this->outcome) {
            'HIT_TP3' => 'badge-pass',
            'HIT_TP2' => 'badge-pass',
            'HIT_TP1' => 'badge-pass',
            'HIT_BE'  => 'badge-blue',
            'HIT_SL'  => 'badge-short',
            'FILLED'  => 'badge-purple',
            default   => 'badge-blue',
        };
    }

    public function getOutcomeLabelAttribute(): string
    {
        return match ($this->outcome) {
            'HIT_TP3'   => '🚀 Hit TP3 (+4.0R)',
            'HIT_TP2'   => '🎯 Hit TP2 (+2.5R)',
            'HIT_TP1'   => '✅ Hit TP1 (+1.5R)',
            'HIT_BE'    => '🛡️ Break-Even (0R)',
            'HIT_SL'    => '🛑 Hit SL (-1.0R)',
            'FILLED'    => '🟣 Filled (In Trade)',
            'CANCELLED' => '❌ Cancelled',
            default     => '🔹 Order Sent',
        };
    }
}
