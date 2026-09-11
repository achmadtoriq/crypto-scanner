<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signal extends Model
{
    use HasFactory;

    protected $fillable = [
        'coin_id', 'direction', 'entry_price', 'stop_loss',
        'take_profit_1', 'take_profit_2', 'take_profit_3',
        'leverage', 'confidence_score', 'status', 'outcome', 'tier',
        'highest_price_reached', 'lowest_price_reached', 'pnl_pct', 'closed_at',
        'telegram_message', 'chart_url', 'note', 'sent_at',
        'interval', 'atr_at_signal', 'rsi_at_signal', 'volume_spike_at_signal',
    ];

    protected $casts = [
        'entry_price'            => 'decimal:8',
        'stop_loss'              => 'decimal:8',
        'take_profit_1'          => 'decimal:8',
        'take_profit_2'          => 'decimal:8',
        'take_profit_3'          => 'decimal:8',
        'highest_price_reached'  => 'decimal:8',
        'lowest_price_reached'   => 'decimal:8',
        'pnl_pct'                => 'decimal:2',
        'leverage'               => 'integer',
        'confidence_score'       => 'decimal:2',
        'atr_at_signal'          => 'decimal:8',
        'rsi_at_signal'          => 'decimal:4',
        'volume_spike_at_signal' => 'decimal:4',
        'sent_at'                => 'datetime',
        'closed_at'              => 'datetime',
    ];

    public function coin(): BelongsTo
    {
        return $this->belongsTo(Coin::class);
    }

    /** Risk/Reward ratio untuk TP1 */
    public function getRr1Attribute(): float
    {
        $risk = abs((float)$this->entry_price - (float)$this->stop_loss);
        if ($risk == 0) return 0;
        return abs((float)$this->take_profit_1 - (float)$this->entry_price) / $risk;
    }

    /** Hitung persentase SL dari entry */
    public function getSlPctAttribute(): float
    {
        if ((float)$this->entry_price == 0) return 0;
        return abs((float)$this->entry_price - (float)$this->stop_loss) / (float)$this->entry_price * 100;
    }

    /**
     * Hitung status area entry & rekomendasi eksekusi saat ini
     */
    public function getEntryProximityInfoAttribute(): array
    {
        $currentPrice = $this->coin && $this->coin->last_price ? (float)$this->coin->last_price : (float)$this->entry_price;
        $entry        = (float)$this->entry_price;
        if ($entry == 0) return ['label' => 'IN ENTRY ZONE', 'badge' => 'badge-pass', 'advice' => 'Ideal for Market/Limit Order'];

        $diffPct = (($currentPrice - $entry) / $entry) * 100;

        if ($this->direction === 'LONG') {
            if (abs($diffPct) <= 0.3) {
                return ['label' => '🟢 IN ENTRY ZONE', 'badge' => 'badge-pass', 'advice' => 'Ideal zone for Market or Limit Order'];
            } elseif ($diffPct < -0.3) {
                return ['label' => '🔵 DISCOUNT ZONE', 'badge' => 'badge-blue', 'advice' => 'Price below entry - Great Limit fill zone!'];
            } elseif ($diffPct > 0.3 && $diffPct <= 1.5) {
                return ['label' => '🟡 WAIT FOR DIP', 'badge' => 'badge-pending', 'advice' => 'Set Limit Order at Entry ($' . fmtPrice($entry) . ')'];
            } else {
                return ['label' => '🔴 PRICE RUNNING (NO FOMO)', 'badge' => 'badge-short', 'advice' => 'Price moved +' . number_format($diffPct, 1) . '% - Do NOT chase!'];
            }
        } else {
            // SHORT
            if (abs($diffPct) <= 0.3) {
                return ['label' => '🟢 IN ENTRY ZONE', 'badge' => 'badge-pass', 'advice' => 'Ideal zone for Market or Limit Short'];
            } elseif ($diffPct > 0.3) {
                return ['label' => '🔵 DISCOUNT ZONE', 'badge' => 'badge-blue', 'advice' => 'Price above entry - Great Limit fill zone!'];
            } elseif ($diffPct < -0.3 && $diffPct >= -1.5) {
                return ['label' => '🟡 WAIT FOR RETEST', 'badge' => 'badge-pending', 'advice' => 'Set Limit Order at Entry ($' . fmtPrice($entry) . ')'];
            } else {
                return ['label' => '🔴 PRICE RUNNING (NO FOMO)', 'badge' => 'badge-short', 'advice' => 'Price dropped - Do NOT chase!'];
            }
        }
    }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeSent($query) { return $query->where('status', 'sent'); }
    public function scopeToday($query) { return $query->whereDate('created_at', today()); }
    public function scopeActiveOutcome($query) { return $query->where('outcome', 'pending'); }
    public function scopeClosedOutcome($query) { return $query->where('outcome', '!=', 'pending'); }
    public function scopeWins($query) { return $query->whereIn('outcome', ['hit_tp1', 'hit_tp2', 'hit_tp3']); }
    public function scopeLosses($query) { return $query->where('outcome', 'hit_sl'); }

    public function getDirectionColorAttribute(): string
    {
        return $this->direction === 'LONG' ? 'green' : 'red';
    }

    public function getDirectionEmojiAttribute(): string
    {
        return $this->direction === 'LONG' ? '🟢' : '🔴';
    }

    public function getOutcomeBadgeColorAttribute(): string
    {
        return match ($this->outcome) {
            'hit_tp3' => 'green',
            'hit_tp2' => 'green',
            'hit_tp1' => 'green',
            'hit_sl'  => 'red',
            'expired' => 'yellow',
            default   => 'blue',
        };
    }

    public function getOutcomeLabelAttribute(): string
    {
        return match ($this->outcome) {
            'hit_tp3' => '🚀 Hit TP3 (+4.0R)',
            'hit_tp2' => '🎯 Hit TP2 (+2.5R)',
            'hit_tp1' => '✅ Hit TP1 (+1.5R)',
            'hit_sl'  => '🛑 Hit SL (-1.0R)',
            'expired' => '⌛ Expired',
            default   => '⏳ Active Tracking',
        };
    }
}
