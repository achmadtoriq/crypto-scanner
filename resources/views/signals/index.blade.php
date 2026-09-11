@extends('layouts.app')

@section('title', 'Signals')
@section('page-title', '⚡ Signal History')
@section('page-subtitle', 'All generated trading signals & forward-testing outcomes')

@section('topbar-actions')
    <a href="{{ route('signals.export') }}" class="btn btn-ghost">📥 Export CSV</a>
    <form method="POST" action="{{ route('signals.track') }}" style="display:inline">
        @csrf
        <button type="submit" class="btn btn-ghost">🎯 Track TP/SL Outcomes</button>
    </form>
@endsection

@section('content')

{{-- Filter Bar --}}
<form method="GET" action="{{ route('signals.index') }}" class="filter-bar">
    <input type="text" name="coin" placeholder="🔍 Search coin..." class="form-input" value="{{ request('coin') }}">
    <select name="direction" class="form-select">
        <option value="">All Directions</option>
        <option value="LONG"  {{ request('direction')=='LONG'  ? 'selected' : '' }}>🟢 LONG</option>
        <option value="SHORT" {{ request('direction')=='SHORT' ? 'selected' : '' }}>🔴 SHORT</option>
    </select>
    <select name="tier" class="form-select">
        <option value="">All Tiers</option>
        <option value="VIP"      {{ request('tier')=='VIP'      ? 'selected' : '' }}>⭐ VIP Tier</option>
        <option value="STANDARD" {{ request('tier')=='STANDARD' ? 'selected' : '' }}>📡 Standard</option>
    </select>
    <select name="outcome" class="form-select">
        <option value="">All Outcomes</option>
        <option value="hit_tp3"  {{ request('outcome')=='hit_tp3'  ? 'selected' : '' }}>🚀 Hit TP3</option>
        <option value="hit_tp2"  {{ request('outcome')=='hit_tp2'  ? 'selected' : '' }}>🎯 Hit TP2</option>
        <option value="hit_tp1"  {{ request('outcome')=='hit_tp1'  ? 'selected' : '' }}>✅ Hit TP1</option>
        <option value="hit_sl"   {{ request('outcome')=='hit_sl'   ? 'selected' : '' }}>🛑 Hit SL</option>
        <option value="pending"  {{ request('outcome')=='pending'  ? 'selected' : '' }}>⏳ Tracking</option>
    </select>
    <input type="date" name="date" class="form-input" value="{{ request('date') }}" style="max-width:160px">
    <select name="min_confidence" class="form-select" style="max-width:160px">
        <option value="">All Confidence</option>
        <option value="50" {{ request('min_confidence')==='50' ? 'selected' : '' }}>≥50% Conf</option>
        <option value="70" {{ request('min_confidence')==='70' ? 'selected' : '' }}>≥70% Conf</option>
        <option value="90" {{ request('min_confidence')==='90' ? 'selected' : '' }}>≥90% Conf</option>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="{{ route('signals.index') }}" class="btn btn-ghost">Reset</a>
</form>

<div class="card">
    @if($signals->isEmpty())
        <div class="empty-state">
            <div class="icon">📭</div>
            <h3>No signals found</h3>
            <p>Run the scanner to generate trading signals</p>
        </div>
    @else
        <div class="table-container">
            <table id="signals-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Coin</th>
                        <th>Tier</th>
                        <th>Direction</th>
                        <th>Live Price (Tick)</th>
                        <th>Entry</th>
                        <th>Stop Loss</th>
                        <th>TP1 Target</th>
                        <th>Live PnL</th>
                        <th>% to TP1</th>
                        <th>Age</th>
                        <th>Outcome</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($signals as $signal)
                    @php
                        $symbol = strtolower($signal->coin->symbol ?? 'btcusdt');
                        $entry  = (float)$signal->entry_price;
                        $sl     = (float)$signal->stop_loss;
                        $tp1    = (float)$signal->take_profit_1;
                        $dir    = $signal->direction;
                        $lev    = (int)($signal->leverage ?? 10);
                        $lastP  = (float)($signal->coin->last_price ?? $entry);
                    @endphp
                    <tr id="signal-row-{{ $signal->id }}" data-symbol="{{ strtoupper($symbol) }}" data-entry="{{ $entry }}" data-sl="{{ $sl }}" data-tp1="{{ $tp1 }}" data-dir="{{ $dir }}" data-lev="{{ $lev }}">
                        <td class="text-muted mono">{{ $signal->id }}</td>
                        <td>
                            <a href="{{ route('signals.show', $signal) }}" style="color:var(--blue);font-weight:700">
                                {{ $signal->coin->base_asset ?? '?' }}
                            </a>
                            <span style="color:var(--text-muted);font-size:11px">/USDT</span>
                        </td>
                        <td>
                            @if($signal->tier === 'VIP')
                                <span class="badge badge-purple">⭐ VIP</span>
                            @else
                                <span class="badge badge-blue">STD</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ strtolower($signal->direction) }}">
                                {{ $signal->direction }}
                            </span>
                        </td>
                        <td class="mono font-bold live-price-cell" id="price-{{ $signal->id }}" style="font-size:13px" data-live-price="{{ $signal->coin->symbol ?? 'BTCUSDT' }}">
                            ${{ fmtPrice($lastP) }}
                        </td>
                        <td class="price mono" data-live-price="{{ $signal->coin->symbol ?? 'BTCUSDT' }}">${{ fmtPrice($entry) }}</td>
                        <td class="price mono" style="color:var(--red)">${{ fmtPrice($sl) }}</td>
                        <td class="price mono" style="color:var(--green)">${{ fmtPrice($tp1) }}</td>
                        <td class="mono font-bold live-pnl-cell" id="pnl-{{ $signal->id }}" style="font-size:12px;color:var(--text-muted)">
                            0.00%
                        </td>
                        <td class="mono" style="font-size:11px;color:var(--text-muted)" id="pct-tp1-{{ $signal->id }}">
                            @php
                                $distTp1 = $entry > 0 ? (($tp1 - $entry) / $entry) * 100 : 0;
                                $distSigned = $dir === 'LONG' ? $distTp1 : -$distTp1;
                            @endphp
                            <span style="color:{{ $distSigned > 0 ? 'var(--green)' : 'var(--red)' }}">
                                {{ $distSigned >= 0 ? '+' : '' }}{{ number_format($distSigned, 2) }}%
                            </span>
                        </td>
                        <td style="font-size:11px;color:var(--text-muted)">
                            {{ $signal->created_at->diffForHumans(null, true) }}
                        </td>
                        <td>
                            <span class="badge badge-{{ $signal->outcome_badge_color }}" id="outcome-{{ $signal->id }}">
                                {{ $signal->outcome_label }}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:4px">
                                <a href="{{ route('signals.show', $signal) }}" class="btn btn-ghost btn-sm">View</a>
                                @if($signal->status === 'pending')
                                    <form method="POST" action="{{ route('signals.cancel', $signal) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--yellow)">Cancel</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ======= ⚡ BINANCE WEBSOCKET TICK-BY-TICK LIVE ENGINE ======= --}}
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ws = new WebSocket("wss://stream.binance.com:9443/ws/!ticker@arr");
            const rows = document.querySelectorAll("tr[id^='signal-row-']");

            ws.onmessage = function (event) {
                const tickers = JSON.parse(event.data);
                const tickerMap = {};
                tickers.forEach(t => tickerMap[t.s] = parseFloat(t.c));

                rows.forEach(row => {
                    const id     = row.id.replace("signal-row-", "");
                    const symbol = row.getAttribute("data-symbol");
                    const entry  = parseFloat(row.getAttribute("data-entry"));
                    const sl     = parseFloat(row.getAttribute("data-sl"));
                    const tp1    = parseFloat(row.getAttribute("data-tp1"));
                    const dir    = row.getAttribute("data-dir");
                    const lev    = parseInt(row.getAttribute("data-lev")) || 10;

                    const curPrice = tickerMap[symbol];
                    if (!curPrice || isNaN(curPrice)) return;

                    const priceEl   = document.getElementById("price-" + id);
                    const pnlEl     = document.getElementById("pnl-" + id);
                    const outcomeEl = document.getElementById("outcome-" + id);

                    // 1. Format Live Price
                    let decimals = 4;
                    if (curPrice >= 100) decimals = 2;
                    else if (curPrice < 0.001) decimals = 8;
                    else if (curPrice < 1) decimals = 6;

                    if (priceEl) {
                        const oldPrice = parseFloat(priceEl.innerText.replace("$", "").replace(",", "")) || curPrice;
                        priceEl.innerText = "$" + curPrice.toFixed(decimals);

                        // Visual Tick Flash
                        if (curPrice > oldPrice) {
                            priceEl.style.color = "#00d4a0";
                            priceEl.style.textShadow = "0 0 8px rgba(0,212,160,0.6)";
                        } else if (curPrice < oldPrice) {
                            priceEl.style.color = "#ff4d4d";
                            priceEl.style.textShadow = "0 0 8px rgba(255,77,77,0.6)";
                        }
                    }

                    // 2. Calculate Live PnL %
                    if (pnlEl && entry > 0) {
                        let pnlPct = 0;
                        if (dir === "LONG") {
                            pnlPct = ((curPrice - entry) / entry) * 100 * lev;
                        } else {
                            pnlPct = ((entry - curPrice) / entry) * 100 * lev;
                        }

                        const pnlStr = (pnlPct >= 0 ? "+" : "") + pnlPct.toFixed(2) + "%";
                        pnlEl.innerText = pnlStr;
                        pnlEl.style.color = pnlPct >= 0 ? "#00d4a0" : "#ff4d4d";
                    }

                    // 3. Real-Time TP/SL Live Trigger Detection
                    if (outcomeEl) {
                        if (dir === "LONG") {
                            if (curPrice >= tp1) {
                                outcomeEl.className = "badge badge-pass";
                                outcomeEl.innerText = "🚀 LIVE TP1 HIT!";
                            } else if (curPrice <= sl) {
                                outcomeEl.className = "badge badge-short";
                                outcomeEl.innerText = "🛑 LIVE SL HIT!";
                            }
                        } else {
                            if (curPrice <= tp1) {
                                outcomeEl.className = "badge badge-pass";
                                outcomeEl.innerText = "🚀 LIVE TP1 HIT!";
                            } else if (curPrice >= sl) {
                                outcomeEl.className = "badge badge-short";
                                outcomeEl.innerText = "🛑 LIVE SL HIT!";
                            }
                        }
                    }
                });
            };
        });
        </script>
        <div style="padding:12px 16px;border-top:1px solid var(--border);background:var(--bg-800);display:flex;align-items:center;gap:10px">
            <span style="font-size:12px;color:var(--text-muted)">🚀 Quick Actions:</span>
            <button onclick="copyBestSignals()" class="btn btn-ghost btn-sm">📋 Copy Best Signals</button>
            <span id="copyFeedback" style="font-size:12px;color:var(--green);opacity:0;transition:opacity 0.3s">✅ Copied to clipboard!</span>
        </div>
        <script>
        function copyBestSignals() {
            const rows = document.querySelectorAll('#signals-table tbody tr');
            let text = '📡 CRYPTO SIGNAL SCANNER EXPORT\n';
            text += '================================\n';
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length < 6) return;
                const coin = cells[1]?.innerText?.trim();
                const dir  = cells[3]?.innerText?.trim();
                const entry= cells[5]?.innerText?.trim();
                const sl   = cells[6]?.innerText?.trim();
                const tp1  = cells[7]?.innerText?.trim();
                if (coin && entry) text += `${coin} ${dir} | Entry:${entry} | SL:${sl} | TP1:${tp1}\n`;
            });
            navigator.clipboard.writeText(text).then(() => {
                const fb = document.getElementById('copyFeedback');
                fb.style.opacity = '1';
                setTimeout(() => fb.style.opacity = '0', 2000);
            });
        }
        </script>
        <div class="pagination-wrapper">
            <div class="pagination-info">
                Showing {{ $signals->firstItem() }}–{{ $signals->lastItem() }} of {{ $signals->total() }} signals
            </div>
            {{ $signals->links() }}
        </div>
    @endif
</div>
@push('scripts')
<script>
window.addEventListener('binance-tick', (e) => {
    const { symbol, price } = e.detail;
    const rows = document.querySelectorAll(`tr[data-symbol="${symbol}"]`);
    rows.forEach(row => {
        const id = row.id.replace("signal-row-", "");
        const entry = parseFloat(row.getAttribute("data-entry"));
        const tp1 = parseFloat(row.getAttribute("data-tp1"));
        const dir = row.getAttribute("data-dir");
        const lev = parseInt(row.getAttribute("data-lev")) || 10;

        const pnlEl = document.getElementById("pnl-" + id);
        if (pnlEl && entry > 0) {
            let pnlPct = 0;
            if (dir === "LONG") {
                pnlPct = ((price - entry) / entry) * 100 * lev;
            } else {
                pnlPct = ((entry - price) / entry) * 100 * lev;
            }
            pnlEl.innerText = (pnlPct >= 0 ? "+" : "") + pnlPct.toFixed(2) + "%";
            pnlEl.style.color = pnlPct >= 0 ? "#00d4a0" : "#ff4d4d";
        }

        const pctTp1El = document.getElementById("pct-tp1-" + id);
        if (pctTp1El && entry > 0) {
            // Update % to TP1 based on live price
            let distSigned = 0;
            if (dir === 'LONG') {
                distSigned = ((tp1 - price) / price) * 100;
            } else {
                distSigned = ((price - tp1) / tp1) * 100;
            }
            pctTp1El.innerHTML = `<span style="color:${distSigned >= 0 ? 'var(--green)' : 'var(--red)'}">${distSigned >= 0 ? '+' : ''}${distSigned.toFixed(2)}%</span>`;
        }
    });
});
</script>
@endpush

@endsection
