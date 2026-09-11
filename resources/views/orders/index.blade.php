@extends('layouts.app')

@section('title', 'Order Execution Journal')
@section('page-title', '📜 Order Execution Journal')
@section('page-subtitle', 'Riwayat lengkap eksekusi order (Binance, Bybit, Paper Trading) & pencatatan posisi real-time')

@section('content')

{{-- ======= SUMMARY STATS GRID ======= --}}
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card blue">
        <div class="stat-icon">📜</div>
        <div class="stat-label">Total Executed Orders</div>
        <div class="stat-value" style="color:var(--blue)">{{ $totalOrders }}</div>
        <div class="stat-change">Total Tickets Logged</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">💵</div>
        <div class="stat-label">Total Margin Used</div>
        <div class="stat-value" style="color:var(--green)">${{ number_format($totalMargin, 2) }}</div>
        <div class="stat-change">Capital Allocated</div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon">📊</div>
        <div class="stat-label">Total Position Exposure</div>
        <div class="stat-value" style="color:var(--purple)">${{ number_format($totalPosition, 2) }}</div>
        <div class="stat-change">Notional Position Size</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon">🏛️</div>
        <div class="stat-label">Exchange Breakdown</div>
        <div class="stat-value" style="font-size:18px;color:var(--gold)">
            {{ $binanceCount }} Binance &bull; {{ $bybitCount }} Bybit
        </div>
        <div class="stat-change">API Orders Route</div>
    </div>
</div>

{{-- ======= FILTER BAR ======= --}}
<div class="card mb-4" style="background:var(--bg-700);padding:14px 20px">
    <form method="GET" action="{{ route('orders.index') }}" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="font-size:12px;font-weight:700;color:var(--text-muted)">EXCHANGE:</div>
            <select name="exchange" onchange="this.form.submit()" style="background:var(--bg-800);border:1px solid var(--border);color:var(--text-primary);padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;outline:none">
                <option value="all"     {{ $selectedExchange === 'all'     ? 'selected' : '' }}>All Exchanges</option>
                <option value="Binance" {{ $selectedExchange === 'Binance' ? 'selected' : '' }}>🔸 Binance Futures</option>
                <option value="Bybit"   {{ $selectedExchange === 'Bybit'   ? 'selected' : '' }}>🟡 Bybit Perpetual</option>
                <option value="Paper"   {{ $selectedExchange === 'Paper'   ? 'selected' : '' }}>🧪 Paper Trading</option>
            </select>

            <div style="font-size:12px;font-weight:700;color:var(--text-muted);margin-left:8px">DIRECTION:</div>
            <select name="direction" onchange="this.form.submit()" style="background:var(--bg-800);border:1px solid var(--border);color:var(--text-primary);padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;outline:none">
                <option value="all"   {{ $selectedDirection === 'all'   ? 'selected' : '' }}>All Directions</option>
                <option value="LONG"  {{ $selectedDirection === 'LONG'  ? 'selected' : '' }}>🟢 LONG</option>
                <option value="SHORT" {{ $selectedDirection === 'SHORT' ? 'selected' : '' }}>🔴 SHORT</option>
            </select>
        </div>
        <div style="font-size:12px;color:var(--text-muted)">
            📋 Direct Order Execution Journal & Audit Log
        </div>
    </form>
</div>

{{-- ======= ORDER HISTORY TABLE ======= --}}
<div class="card" style="padding:0;overflow:hidden">
    @if($orders->isEmpty())
        <div class="empty-state" style="padding:40px;text-align:center">
            <div class="icon" style="font-size:40px;margin-bottom:10px">📭</div>
            <h3 style="margin:0 0 6px 0;color:var(--text-primary)">Belum Ada Order Ter-eksekusi</h3>
            <p style="color:var(--text-muted);font-size:13px">Buka salah satu sinyal dan klik "EXECUTE ORDER NOW" untuk merekam order pertama Anda.</p>
        </div>
    @else
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:12px;text-align:center" class="table-orders">
                <thead>
                    <tr style="background:var(--bg-900);border-bottom:2px solid var(--border);color:var(--text-muted);font-size:11px;text-transform:uppercase">
                        <th style="padding:12px;text-align:left">Ticket ID</th>
                        <th style="padding:12px;text-align:left">Koin / Symbol</th>
                        <th style="padding:12px">Direction</th>
                        <th style="padding:12px">Exchange</th>
                        <th style="padding:12px">Order Type</th>
                        <th style="padding:12px">Entry Price</th>
                        <th style="padding:12px">Stop Loss</th>
                        <th style="padding:12px">Take Profit</th>
                        <th style="padding:12px;color:var(--blue)">Margin ($)</th>
                        <th style="padding:12px;color:var(--green)">Position Size ($)</th>
                        <th style="padding:12px">Status</th>
                        <th style="padding:12px;color:var(--green)">Target Outcome Marker</th>
                        <th style="padding:12px">Datetime</th>
                        <th style="padding:12px">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $ord)
                    @php
                        $targetSignalId = $ord->signal_id;
                        if (!$targetSignalId && $ord->coin) {
                            $targetSignalId = optional($ord->coin->signals()->latest()->first())->id;
                        }
                    @endphp
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:12px;text-align:left;font-weight:800;font-family:var(--font-mono)">
                            @if($targetSignalId)
                                <a href="{{ route('signals.show', $targetSignalId) }}" style="color:var(--green);text-decoration:underline;display:inline-flex;align-items:center;gap:4px" title="Buka Detail Signal #{{ $targetSignalId }}">
                                    {{ $ord->ticket_id }} 🔗
                                </a>
                            @else
                                <span style="color:var(--green)">{{ $ord->ticket_id }}</span>
                            @endif
                        </td>
                        <td style="padding:12px;text-align:left;font-weight:700">
                            @if($targetSignalId)
                                <a href="{{ route('signals.show', $targetSignalId) }}" style="color:var(--text-primary);text-decoration:none;font-weight:800" title="Buka Detail Signal #{{ $targetSignalId }}">
                                    {{ $ord->symbol }}
                                </a>
                            @elseif($ord->coin_id)
                                <a href="{{ route('coins.show', $ord->coin_id) }}" style="color:var(--text-primary);text-decoration:none">
                                    {{ $ord->symbol }}
                                </a>
                            @else
                                {{ $ord->symbol }}
                            @endif
                        </td>
                        <td style="padding:12px">
                            <span class="badge {{ $ord->direction === 'LONG' ? 'badge-pass' : 'badge-short' }}">
                                {{ $ord->direction === 'LONG' ? '🟢 LONG' : '🔴 SHORT' }}
                            </span>
                        </td>
                        <td style="padding:12px;font-weight:700;color:var(--text-primary)">
                            {{ $ord->exchange }}
                        </td>
                        <td style="padding:12px">
                            <span class="badge badge-blue">{{ $ord->order_type }} ({{ $ord->leverage }}x)</span>
                        </td>
                        <td style="padding:12px;font-family:var(--font-mono)">
                            ${{ fmtPrice($ord->entry_price) }}
                        </td>
                        <td style="padding:12px;font-family:var(--font-mono);color:var(--red)">
                            ${{ fmtPrice($ord->stop_loss) }}
                        </td>
                        <td style="padding:12px;font-family:var(--font-mono);color:var(--green)">
                            ${{ fmtPrice($ord->take_profit ?? $ord->entry_price) }}
                        </td>
                        <td style="padding:12px;font-family:var(--font-mono);font-weight:700;color:var(--blue)">
                            ${{ number_format($ord->margin_usd, 2) }}
                        </td>
                        <td style="padding:12px;font-family:var(--font-mono);font-weight:800;color:var(--green)">
                            ${{ number_format($ord->position_size_usd, 2) }}
                        </td>
                        <td style="padding:12px">
                            <span class="badge {{ $ord->status_badge }}">
                                {{ $ord->status }}
                            </span>
                        </td>
                        <td style="padding:12px">
                            <form method="POST" action="{{ route('orders.update-outcome', $ord->id) }}" style="display:inline">
                                @csrf
                                @method('PATCH')
                                <select name="outcome" onchange="this.form.submit()" style="background:var(--bg-900);border:1px solid var(--border);color:var(--text-primary);padding:4px 8px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;outline:none">
                                    <option value="PENDING"   {{ $ord->outcome === 'PENDING'   ? 'selected' : '' }}>🔹 Order Sent</option>
                                    <option value="FILLED"    {{ $ord->outcome === 'FILLED'    ? 'selected' : '' }}>🟣 Filled (In Trade)</option>
                                    <option value="HIT_TP1"   {{ $ord->outcome === 'HIT_TP1'   ? 'selected' : '' }}>✅ HIT TP1 (+1.5R)</option>
                                    <option value="HIT_TP2"   {{ $ord->outcome === 'HIT_TP2'   ? 'selected' : '' }}>🎯 HIT TP2 (+2.5R)</option>
                                    <option value="HIT_TP3"   {{ $ord->outcome === 'HIT_TP3'   ? 'selected' : '' }}>🚀 HIT TP3 (+4.0R)</option>
                                    <option value="HIT_BE"    {{ $ord->outcome === 'HIT_BE'    ? 'selected' : '' }}>🛡️ Break-Even (0R)</option>
                                    <option value="HIT_SL"    {{ $ord->outcome === 'HIT_SL'    ? 'selected' : '' }}>🛑 HIT SL (-1.0R)</option>
                                    <option value="CANCELLED" {{ $ord->outcome === 'CANCELLED' ? 'selected' : '' }}>❌ Cancelled</option>
                                </select>
                            </form>
                        </td>
                        <td style="padding:12px;color:var(--text-muted);font-size:11px">
                            {{ $ord->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td style="padding:12px;white-space:nowrap">
                            @if($targetSignalId)
                                <a href="{{ route('signals.show', $targetSignalId) }}" class="btn btn-ghost btn-sm" style="font-size:10px;padding:3px 8px;color:var(--blue);border:1px solid rgba(77,158,255,0.3);margin-right:4px">⚡ Signal</a>
                            @endif
                            <form method="POST" action="{{ route('orders.destroy', $ord->id) }}" onsubmit="return confirm('Hapus log order ini?')" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red);padding:2px 6px" title="Hapus order">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:16px">
            {{ $orders->links() }}
        </div>
    @endif
</div>

@endsection
