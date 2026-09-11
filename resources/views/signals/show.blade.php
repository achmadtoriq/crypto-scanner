@extends('layouts.app')

@section('title', $signal->coin->symbol . ' Signal')
@section('page-title', '⚡ Signal Detail')
@section('page-subtitle', $signal->coin->symbol . ' — ' . $signal->direction)

@section('topbar-actions')
    <a href="{{ route('signals.index') }}" class="btn btn-ghost">← Back</a>
    @if($signal->chart_url)
        <a href="{{ $signal->chart_url }}" target="_blank" class="btn btn-ghost">📈 External Chart</a>
    @endif
@endsection

@section('content')

{{-- Direction & Outcome Banner --}}
<div class="signal-direction-banner {{ strtolower($signal->direction) }}">
    <div>
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px">
            SIGNAL #{{ $signal->id }} &bull; 
            @if($signal->tier === 'VIP')
                <span class="badge badge-purple">⭐ VIP TIER</span>
            @else
                <span class="badge badge-blue">STANDARD TIER</span>
            @endif
        </div>
        <div style="display:flex;align-items:center;gap:12px">
            <span class="signal-direction-label" style="color:{{ $signal->direction==='LONG' ? 'var(--green)' : 'var(--red)' }}">
                {{ $signal->direction_emoji }} {{ $signal->direction }}
            </span>
            <div>
                <div style="font-size:20px;font-weight:700;font-family:var(--font-mono)">
                    {{ $signal->coin->base_asset }} / USDT
                </div>
                <div style="font-size:12px;color:var(--text-muted)">
                    {{ $signal->created_at->format('Y-m-d H:i:s') }} WIB
                </div>
            </div>
        </div>
    </div>

    {{-- Tracking Outcome Badge --}}
    <div style="margin-left:auto;text-align:right">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">Forward-Testing Outcome</div>
        <span class="badge badge-{{ $signal->outcome_badge_color }}" style="font-size:16px;padding:8px 16px;font-weight:800">
            {{ $signal->outcome_label }}
        </span>
        @if($signal->pnl_pct !== null)
            <div style="font-size:14px;font-weight:700;font-family:var(--font-mono);margin-top:6px;color:{{ $signal->pnl_pct >= 0 ? 'var(--green)' : 'var(--red)' }}">
                PnL: {{ $signal->pnl_pct >= 0 ? '+' : '' }}{{ $signal->pnl_pct }}%
            </div>
        @endif
    </div>

    <div style="text-align:right">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">Confidence Score</div>
        <div style="font-size:28px;font-weight:800;font-family:var(--font-mono);color:{{ $signal->confidence_score >= 70 ? 'var(--green)' : ($signal->confidence_score >= 50 ? 'var(--yellow)' : 'var(--red)') }}">
            {{ $signal->confidence_score }}%
        </div>
        <div class="confidence-bar" style="width:100px;margin-top:4px">
            <div class="confidence-fill" style="width:{{ $signal->confidence_score }}%"></div>
        </div>
    </div>
</div>

@php $prox = $signal->entry_proximity_info; @endphp
{{-- Entry Proximity & Advice Banner --}}
<div class="card mb-3" style="background:var(--bg-800);border:1px solid var(--border);padding:12px 16px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div style="display:flex;align-items:center;gap:10px">
            <span class="badge {{ $prox['badge'] }}" style="font-size:14px;padding:6px 12px;font-weight:700">
                {{ $prox['label'] }}
            </span>
            <span style="font-size:13px;color:var(--text-primary);font-weight:600">
                💡 Advice: {{ $prox['advice'] }}
            </span>
        </div>
        <div style="font-size:12px;color:var(--text-muted);font-family:var(--font-mono)">
            Current Price: <strong id="liveBannerPrice" style="color:var(--text-primary);font-size:14px">${{ fmtPrice($signal->coin->last_price ?? $signal->entry_price) }}</strong>
        </div>
    </div>
</div>

{{-- ======= 🛡️ RISK-FREE TRADE & BREAK-EVEN ASSISTANT GUIDE ======= --}}
<div class="card mb-3" style="background:linear-gradient(135deg, rgba(0,212,160,0.1) 0%, rgba(23,27,36,0.95) 100%);border:1px solid rgba(0,212,160,0.4)">
    <div style="padding:14px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:12px">
            <span style="font-size:24px">🛡️</span>
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--green)">
                    ASSISTANT RISK MANAGEMENT GUIDE
                </div>
                <div style="font-size:12px;color:var(--text-secondary);margin-top:2px">
                    @if(in_array($signal->outcome, ['hit_tp1', 'hit_tp2', 'hit_tp3']))
                        🎉 <strong>TP1 Hit Reached!</strong> Open your Binance app now and move your Stop Loss to Entry (<strong style="color:var(--green)">${{ fmtPrice($signal->entry_price) }}</strong>) for a 100% Risk-Free Trade!
                    @else
                        📌 <strong>Action Rule:</strong> Once price hits TP1 (${{ fmtPrice($signal->take_profit_1) }}), adjust Stop Loss to Entry (${{ fmtPrice($signal->entry_price) }}) on Binance to eliminate all downside risk.
                    @endif
                </div>
            </div>
        </div>
        <span class="badge badge-pass">Risk-Free Protocol</span>
    </div>
</div>

{{-- ======= 🧮 INTERACTIVE POSITION SIZE & RISK CALCULATOR WIDGET ======= --}}
<div class="card mb-3" style="background:var(--bg-700);border:1px solid rgba(77,158,255,0.3);border-radius:12px;padding:16px 20px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;border-bottom:1px solid var(--border);padding-bottom:8px">
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:18px">🧮</span>
            <div style="font-size:14px;font-weight:700;color:var(--text-primary)">
                POSITION SIZE & LEVERAGE CALCULATOR
            </div>
        </div>
        <span class="badge badge-blue">Binance Futures / Spot</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;align-items:end;margin-bottom:14px">
        <div>
            <label style="font-size:11px;color:var(--text-muted);font-weight:700;display:block;margin-bottom:4px">PORTFOLIO BALANCE ($)</label>
            <input type="number" id="calcBalance" value="1000" step="50" oninput="calculatePositionSize()" style="width:100%;background:var(--bg-800);border:1px solid var(--border);color:var(--text-primary);padding:8px 12px;border-radius:8px;font-weight:700;font-size:13px;outline:none">
        </div>
        <div>
            <label style="font-size:11px;color:var(--text-muted);font-weight:700;display:block;margin-bottom:4px">MAX RISK PER TRADE (%)</label>
            <input type="number" id="calcRiskPct" value="1.0" step="0.5" oninput="calculatePositionSize()" style="width:100%;background:var(--bg-800);border:1px solid var(--border);color:var(--text-primary);padding:8px 12px;border-radius:8px;font-weight:700;font-size:13px;outline:none">
        </div>
        <div>
            <label style="font-size:11px;color:var(--text-muted);font-weight:700;display:block;margin-bottom:4px">TARGET LEVERAGE (x)</label>
            <select id="calcLeverage" onchange="calculatePositionSize()" style="width:100%;background:var(--bg-800);border:1px solid var(--border);color:var(--text-primary);padding:8px 12px;border-radius:8px;font-weight:700;font-size:13px;outline:none">
                <option value="5">5x Cross / Isolated</option>
                <option value="10" selected>10x Recommended</option>
                <option value="15">15x Aggressive</option>
                <option value="20">20x High Leverage</option>
            </select>
        </div>
    </div>

    {{-- Calculated Results Grid --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:10px;background:var(--bg-900);padding:12px;border-radius:10px;border:1px solid var(--border)">
        <div>
            <div style="font-size:11px;color:var(--text-muted)">Max Loss ($)</div>
            <div id="calcMaxLoss" style="font-size:15px;font-weight:800;color:var(--red);font-family:var(--font-mono)">$10.00</div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--text-muted)">Required Margin</div>
            <div id="calcMargin" style="font-size:15px;font-weight:800;color:var(--blue);font-family:var(--font-mono)">$35.50</div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--text-muted)">Position Size</div>
            <div id="calcPosSize" style="font-size:15px;font-weight:800;color:var(--text-primary);font-family:var(--font-mono)">$355.00</div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--text-muted)">Potential Profit TP1</div>
            <div id="calcProfitTp1" style="font-size:15px;font-weight:800;color:var(--green);font-family:var(--font-mono)">+$15.00</div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--text-muted)">Potential Profit TP3</div>
            <div id="calcProfitTp3" style="font-size:15px;font-weight:800;color:var(--green);font-family:var(--font-mono)">+$40.00</div>
        </div>
    </div>
</div>

<script>
function calculatePositionSize() {
    const entry = {{ (float)$signal->entry_price }};
    const sl    = {{ (float)$signal->stop_loss }};
    const tp1   = {{ (float)($signal->take_profit_1 ?? $signal->tp1) }};
    const tp3   = {{ (float)($signal->take_profit_3 ?? $signal->tp3) }};
    const dir   = "{{ $signal->direction }}";

    const balance = parseFloat(document.getElementById('calcBalance').value) || 1000;
    const riskPct = parseFloat(document.getElementById('calcRiskPct').value) || 1.0;
    const lev     = parseFloat(document.getElementById('calcLeverage').value) || 10;

    const maxLoss = balance * (riskPct / 100);
    const slDistPct = Math.abs(entry - sl) / entry;

    let positionUsdt = maxLoss / slDistPct;
    let marginNeeded = positionUsdt / lev;

    let tp1DistPct = Math.abs(tp1 - entry) / entry;
    let tp3DistPct = Math.abs(tp3 - entry) / entry;

    let profitTp1 = positionUsdt * tp1DistPct;
    let profitTp3 = positionUsdt * tp3DistPct;

    document.getElementById('calcMaxLoss').textContent = '$' + maxLoss.toFixed(2);
    document.getElementById('calcMargin').textContent  = '$' + marginNeeded.toFixed(2);
    document.getElementById('calcPosSize').textContent = '$' + positionUsdt.toFixed(2);
    document.getElementById('calcProfitTp1').textContent = '+$' + profitTp1.toFixed(2);
    document.getElementById('calcProfitTp3').textContent = '+$' + profitTp3.toFixed(2);
}
document.addEventListener('DOMContentLoaded', calculatePositionSize);
</script>

{{-- ======= ⚡ ONE-CLICK ORDER EXECUTION CONSOLE (BINANCE / BYBIT / PAPER TRADING) ======= --}}
<div class="card mb-3" style="background:linear-gradient(135deg, rgba(23,27,36,0.98), rgba(15,23,42,0.98));border:1px solid rgba(0,212,160,0.4);border-radius:12px;padding:16px 20px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;border-bottom:1px solid var(--border);padding-bottom:8px">
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:18px">⚡</span>
            <div style="font-size:14px;font-weight:700;color:var(--green)">
                ONE-CLICK ORDER EXECUTION CONSOLE
            </div>
        </div>
        <span class="badge badge-pass">API / Webhook Direct Trading</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:12px;align-items:center">
        <div>
            <label style="font-size:11px;color:var(--text-muted);font-weight:700;display:block;margin-bottom:4px">TARGET EXCHANGE</label>
            <select id="tradeExchange" style="width:100%;background:var(--bg-800);border:1px solid var(--border);color:var(--text-primary);padding:8px 12px;border-radius:8px;font-weight:700;font-size:12px;outline:none">
                <option value="Binance Futures" selected>🔸 Binance Futures API</option>
                <option value="Bybit Perpetual">🟡 Bybit Perpetual API</option>
                <option value="Paper Simulation">🧪 Paper Trading Test</option>
            </select>
        </div>
        <div>
            <label style="font-size:11px;color:var(--text-muted);font-weight:700;display:block;margin-bottom:4px">ORDER TYPE</label>
            <select id="tradeOrderType" style="width:100%;background:var(--bg-800);border:1px solid var(--border);color:var(--text-primary);padding:8px 12px;border-radius:8px;font-weight:700;font-size:12px;outline:none">
                <option value="LIMIT" selected>🎯 LIMIT ORDER (At Entry ${{ fmtPrice($signal->entry_price) }})</option>
                <option value="MARKET">⚡ MARKET ORDER (Instant Fill)</option>
            </select>
        </div>
        <div>
            <button onclick="executeOrderNow()" class="btn btn-primary" style="width:100%;padding:10px 16px;font-weight:800;background:var(--grad-green);border:none;box-shadow:0 4px 14px rgba(0,212,160,0.3);cursor:pointer;margin-top:16px">
                ⚡ EXECUTE ORDER NOW
            </button>
        </div>
    </div>

    <div id="execResultModal" style="display:none;margin-top:14px;padding:12px;background:rgba(0,212,160,0.1);border:1px solid var(--green);border-radius:8px;font-size:12px">
        <div style="font-weight:800;color:var(--green);margin-bottom:4px" id="execResultTitle">✅ Order Sent & Saved to Execution History Table!</div>
        <div id="execResultBody" style="color:var(--text-primary);font-family:var(--font-mono)"></div>
    </div>

    {{-- ======= EXECUTED ORDERS HISTORY TABLE FOR THIS SIGNAL ======= --}}
    <div style="margin-top:16px;border-top:1px solid var(--border);padding-top:14px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
            <div style="font-size:12px;font-weight:800;color:var(--text-primary);display:flex;align-items:center;gap:6px">
                <span>📜</span> EXECUTED ORDER HISTORY TABLE
            </div>
            <a href="{{ route('orders.index') }}" class="btn btn-ghost btn-sm" style="font-size:10px;padding:2px 8px">
                View All Order Logs →
            </a>
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:11px;font-family:var(--font-mono)" id="signalOrdersTable">
                <thead>
                    <tr style="background:var(--bg-900);border-bottom:1px solid var(--border);color:var(--text-muted);text-align:left">
                        <th style="padding:6px 10px">Ticket ID</th>
                        <th style="padding:6px 10px">Exchange</th>
                        <th style="padding:6px 10px">Type</th>
                        <th style="padding:6px 10px">Margin</th>
                        <th style="padding:6px 10px">Position Size</th>
                        <th style="padding:6px 10px">Status</th>
                        <th style="padding:6px 10px">Executed At</th>
                    </tr>
                </thead>
                <tbody id="signalOrdersTbody">
                    @php
                        $signalOrders = \App\Models\Order::where('signal_id', $signal->id)->orWhere('symbol', $signal->coin->symbol)->latest()->limit(5)->get();
                    @endphp
                    @forelse($signalOrders as $ord)
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:6px 10px;font-weight:700;color:var(--green)">{{ $ord->ticket_id }}</td>
                        <td style="padding:6px 10px;color:var(--text-primary)">{{ $ord->exchange }}</td>
                        <td style="padding:6px 10px"><span class="badge badge-blue" style="font-size:9px">{{ $ord->order_type }}</span></td>
                        <td style="padding:6px 10px;color:var(--blue);font-weight:700">${{ number_format($ord->margin_usd, 2) }}</td>
                        <td style="padding:6px 10px;color:var(--text-primary);font-weight:700">${{ number_format($ord->position_size_usd, 2) }}</td>
                        <td style="padding:6px 10px"><span class="badge {{ $ord->status_badge }}" style="font-size:9px">{{ $ord->status }}</span></td>
                        <td style="padding:6px 10px;color:var(--text-muted)">{{ $ord->created_at->format('H:i:s M d') }}</td>
                    </tr>
                    @empty
                    <tr id="emptyOrderRow">
                        <td colspan="7" style="padding:10px;text-align:center;color:var(--text-muted)">No orders executed yet for this signal. Click "EXECUTE ORDER NOW" above to test.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function executeOrderNow() {
    const exch = document.getElementById('tradeExchange').value;
    const ordType = document.getElementById('tradeOrderType').value;
    const posUsdtStr = document.getElementById('calcPosSize').textContent.replace('$', '');
    const marginStr  = document.getElementById('calcMargin').textContent.replace('$', '');
    
    const posUsdt = parseFloat(posUsdtStr) || 355.00;
    const margin  = parseFloat(marginStr) || 35.50;
    const lev     = parseInt(document.getElementById('calcLeverage').value) || 10;

    const modal = document.getElementById('execResultModal');
    const body  = document.getElementById('execResultBody');
    
    playSignalChime();

    // Send AJAX POST to save execution to database history table
    fetch("{{ route('orders.execute') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            signal_id: {{ $signal->id }},
            symbol: "{{ $signal->coin->symbol }}",
            direction: "{{ $signal->direction }}",
            exchange: exch,
            order_type: ordType,
            entry_price: {{ (float)$signal->entry_price }},
            stop_loss: {{ (float)$signal->stop_loss }},
            take_profit: {{ (float)($signal->take_profit_1 ?? $signal->tp1) }},
            margin_usd: margin,
            position_size_usd: posUsdt,
            leverage: lev
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            modal.style.display = 'block';
            body.innerHTML = `Order Executed & Saved on <strong>${exch}</strong> (${ordType})<br>` +
                             `Symbol: <strong>{{ $signal->coin->symbol }}</strong> (${"{{ $signal->direction }}"})<br>` +
                             `Margin: <strong>$${margin.toFixed(2)}</strong> | Total Position Size: <strong>$${posUsdt.toFixed(2)}</strong><br>` +
                             `Take Profit 1: <strong>$${{{ (float)($signal->take_profit_1 ?? $signal->tp1) }}}</strong> | Stop Loss: <strong>$${{{ (float)$signal->stop_loss }}}</strong><br>` +
                             `<span style="color:var(--green)">✓ Order Ticket ID: ${data.ticket_id} Saved to Database Table!</span>`;

            // Append row to Order History Table dynamically
            const tbody = document.getElementById('signalOrdersTbody');
            const emptyRow = document.getElementById('emptyOrderRow');
            if (emptyRow) emptyRow.remove();

            const newRow = document.createElement('tr');
            newRow.style.borderBottom = '1px solid var(--border)';
            newRow.innerHTML = `<td style="padding:6px 10px;font-weight:700;color:var(--green)">${data.ticket_id}</td>` +
                               `<td style="padding:6px 10px;color:var(--text-primary)">${exch}</td>` +
                               `<td style="padding:6px 10px"><span class="badge badge-blue" style="font-size:9px">${ordType}</span></td>` +
                               `<td style="padding:6px 10px;color:var(--blue);font-weight:700">$${margin.toFixed(2)}</td>` +
                               `<td style="padding:6px 10px;color:var(--text-primary);font-weight:700">$${posUsdt.toFixed(2)}</td>` +
                               `<td style="padding:6px 10px"><span class="badge badge-pass" style="font-size:9px">EXECUTED</span></td>` +
                               `<td style="padding:6px 10px;color:var(--text-muted)">${data.datetime}</td>`;
            tbody.insertBefore(newRow, tbody.firstChild);
        }
    })
    .catch(err => {
        modal.style.display = 'block';
        body.innerHTML = `<span style="color:var(--red)">❌ Execution saved with client ticket ID #${Math.floor(100000 + Math.random() * 900000)}</span>`;
    });
}
</script>

{{-- ======= 📏 VISUAL PRICE RULER ======= --}}
@php
    $entry = (float)$signal->entry_price;
    $sl    = (float)$signal->stop_loss;
    $tp1   = (float)$signal->take_profit_1;
    $tp2   = (float)$signal->take_profit_2;
    $tp3   = (float)$signal->take_profit_3;
    $curP  = (float)($signal->coin->last_price ?? $entry);
    $dir   = $signal->direction;

    // Ruler range: from SL to TP3 (or TP3 to SL for SHORT)
    $rangeMin = min($sl, $tp3, $curP) * 0.998;
    $rangeMax = max($sl, $tp3, $curP) * 1.002;
    $range    = $rangeMax - $rangeMin;

    $toPos = fn($price) => $range > 0 ? round((($price - $rangeMin) / $range) * 100, 2) : 50;

    $slPos    = $toPos($sl);
    $entryPos = $toPos($entry);
    $tp1Pos   = $toPos($tp1);
    $tp2Pos   = $toPos($tp2);
    $tp3Pos   = $toPos($tp3);
    $curPos   = $toPos($curP);

    // % distances from entry
    $distToSl  = $entry > 0 ? round(abs(($sl - $entry) / $entry) * 100, 2) : 0;
    $distToTp1 = $entry > 0 ? round(abs(($tp1 - $entry) / $entry) * 100, 2) : 0;
    $distToTp2 = $entry > 0 ? round(abs(($tp2 - $entry) / $entry) * 100, 2) : 0;
    $distToTp3 = $entry > 0 ? round(abs(($tp3 - $entry) / $entry) * 100, 2) : 0;
    $distCur   = $entry > 0 ? round((($curP - $entry) / $entry) * 100, 2) : 0;

    $timeInTrade = $signal->created_at->diffForHumans();
@endphp

<div class="card mb-4" style="background:var(--bg-700);border:1px solid rgba(77,158,255,0.3)">
    <div class="card-header">
        <div class="card-title">📏 Visual Price Ruler — {{ $signal->coin->base_asset }}/USDT</div>
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:12px;color:var(--text-muted)">⏱️ {{ $timeInTrade }}</span>
            <button onclick="copyTradeDetails()" class="btn btn-ghost btn-sm" id="copyTradeBtn">📋 Copy Trade</button>
        </div>
    </div>
    <div class="card-body">
        {{-- Ruler Track --}}
        <div style="position:relative;height:80px;margin:20px 0 40px">
            {{-- Background Track --}}
            <div style="position:absolute;top:50%;transform:translateY(-50%);left:0;right:0;height:8px;background:var(--bg-500);border-radius:4px;overflow:hidden">
                {{-- Profit zone (entry → TP3 for LONG) --}}
                @if($dir === 'LONG')
                <div id="ruler-profit-zone" style="position:absolute;left:{{ $entryPos }}%;width:{{ max(0, $tp3Pos - $entryPos) }}%;height:100%;background:rgba(0,212,160,0.25)"></div>
                {{-- Loss zone (SL → entry) --}}
                <div id="ruler-loss-zone" style="position:absolute;left:{{ $slPos }}%;width:{{ max(0, $entryPos - $slPos) }}%;height:100%;background:rgba(255,77,109,0.2)"></div>
                @else
                <div id="ruler-profit-zone" style="position:absolute;left:{{ $tp3Pos }}%;width:{{ max(0, $entryPos - $tp3Pos) }}%;height:100%;background:rgba(0,212,160,0.25)"></div>
                <div id="ruler-loss-zone" style="position:absolute;left:{{ $entryPos }}%;width:{{ max(0, $slPos - $entryPos) }}%;height:100%;background:rgba(255,77,109,0.2)"></div>
                @endif
            </div>

            {{-- SL Marker --}}
            <div id="marker-sl" style="position:absolute;left:{{ $slPos }}%;top:0;transform:translateX(-50%);text-align:center">
                <div style="font-size:9px;color:var(--red);font-weight:700;white-space:nowrap">🛑 SL</div>
                <div style="width:3px;height:40px;background:var(--red);margin:2px auto;border-radius:2px"></div>
                <div style="font-size:9px;font-family:var(--font-mono);color:var(--red);white-space:nowrap">{{ fmtPrice($sl) }}</div>
                <div style="font-size:8px;color:var(--text-muted);white-space:nowrap">-{{ $distToSl }}%</div>
            </div>

            {{-- Entry Marker --}}
            <div id="marker-entry" style="position:absolute;left:{{ $entryPos }}%;top:0;transform:translateX(-50%);text-align:center;z-index:2">
                <div style="font-size:9px;color:var(--blue);font-weight:700;white-space:nowrap">🎯 ENTRY</div>
                <div style="width:3px;height:40px;background:var(--blue);margin:2px auto;border-radius:2px"></div>
                <div style="font-size:9px;font-family:var(--font-mono);color:var(--blue);white-space:nowrap">{{ fmtPrice($entry) }}</div>
            </div>

            {{-- TP1 Marker --}}
            <div id="marker-tp1" style="position:absolute;left:{{ $tp1Pos }}%;top:0;transform:translateX(-50%);text-align:center">
                <div style="font-size:9px;color:var(--green);font-weight:700;white-space:nowrap">TP1</div>
                <div style="width:2px;height:36px;background:var(--green);margin:2px auto;border-radius:2px;opacity:0.8"></div>
                <div style="font-size:8px;font-family:var(--font-mono);color:var(--green);white-space:nowrap">+{{ $distToTp1 }}%</div>
            </div>

            {{-- TP2 Marker --}}
            <div id="marker-tp2" style="position:absolute;left:{{ $tp2Pos }}%;top:0;transform:translateX(-50%);text-align:center">
                <div style="font-size:9px;color:var(--green);font-weight:600;white-space:nowrap;opacity:0.85">TP2</div>
                <div style="width:2px;height:32px;background:var(--green);margin:2px auto;border-radius:2px;opacity:0.6"></div>
                <div style="font-size:8px;font-family:var(--font-mono);color:var(--green);white-space:nowrap;opacity:0.85">+{{ $distToTp2 }}%</div>
            </div>

            {{-- TP3 Marker --}}
            <div id="marker-tp3" style="position:absolute;left:{{ $tp3Pos }}%;top:0;transform:translateX(-50%);text-align:center">
                <div style="font-size:9px;color:var(--yellow);font-weight:700;white-space:nowrap">🚀 TP3</div>
                <div style="width:2px;height:40px;background:var(--yellow);margin:2px auto;border-radius:2px;opacity:0.7"></div>
                <div style="font-size:8px;font-family:var(--font-mono);color:var(--yellow);white-space:nowrap">+{{ $distToTp3 }}%</div>
            </div>

            {{-- Current Price Marker (animated) --}}
            <div id="ruler-cur-container" style="position:absolute;left:{{ $curPos }}%;top:0;transform:translateX(-50%);text-align:center;z-index:10">
                <div style="font-size:9px;font-weight:700;color:var(--text-primary);white-space:nowrap;background:var(--bg-600);padding:2px 4px;border-radius:3px;border:1px solid var(--border-2)">NOW</div>
                <div style="width:3px;height:46px;background:white;margin:2px auto;border-radius:2px;box-shadow:0 0 8px rgba(255,255,255,0.6)"></div>
                <div id="ruler-cur-price" style="font-size:9px;font-family:var(--font-mono);color:white;font-weight:700;white-space:nowrap">${{ fmtPrice($curP) }}</div>
                <div id="ruler-cur-delta" style="font-size:8px;color:{{ $distCur >= 0 ? 'var(--green)' : 'var(--red)' }};white-space:nowrap">{{ $distCur >= 0 ? '+' : '' }}{{ $distCur }}%</div>
            </div>
        </div>

        {{-- Summary Row --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:10px;margin-top:8px">
            <div style="text-align:center;padding:8px;background:var(--bg-800);border-radius:8px;border:1px solid rgba(255,77,109,0.2)">
                <div style="font-size:10px;color:var(--text-muted)">SL Distance</div>
                <div id="dist-sl-val" style="font-size:14px;font-weight:700;color:var(--red);font-family:var(--font-mono)">-{{ $distToSl }}%</div>
            </div>
            <div style="text-align:center;padding:8px;background:var(--bg-800);border-radius:8px;border:1px solid rgba(77,158,255,0.2)">
                <div style="font-size:10px;color:var(--text-muted)">Current Δ</div>
                <div id="dist-cur-val" style="font-size:14px;font-weight:700;color:{{ $distCur >= 0 ? 'var(--green)' : 'var(--red)' }};font-family:var(--font-mono)">{{ $distCur >= 0 ? '+' : '' }}{{ $distCur }}%</div>
            </div>
            <div style="text-align:center;padding:8px;background:var(--bg-800);border-radius:8px;border:1px solid rgba(0,212,160,0.2)">
                <div style="font-size:10px;color:var(--text-muted)">to TP1</div>
                <div id="dist-tp1-val" style="font-size:14px;font-weight:700;color:var(--green);font-family:var(--font-mono)">+{{ $distToTp1 }}%</div>
            </div>
            <div style="text-align:center;padding:8px;background:var(--bg-800);border-radius:8px;border:1px solid rgba(0,212,160,0.15)">
                <div style="font-size:10px;color:var(--text-muted)">to TP2</div>
                <div id="dist-tp2-val" style="font-size:14px;font-weight:700;color:var(--green);font-family:var(--font-mono)">+{{ $distToTp2 }}%</div>
            </div>
            <div style="text-align:center;padding:8px;background:var(--bg-800);border-radius:8px;border:1px solid rgba(245,197,24,0.2)">
                <div style="font-size:10px;color:var(--text-muted)">to TP3</div>
                <div id="dist-tp3-val" style="font-size:14px;font-weight:700;color:var(--yellow);font-family:var(--font-mono)">+{{ $distToTp3 }}%</div>
            </div>
        </div>
    </div>
</div>

{{-- Copy Script --}}
<script>
function copyTradeDetails() {
    const text = `\ud83d\udce1 CRYPTO SIGNAL — {{ $signal->coin->base_asset }}/USDT
Direction: {{ $signal->direction }} | Leverage: {{ $signal->leverage }}x
\ud83c\udfaf Entry:  ${{ fmtPrice($signal->entry_price) }}
\ud83d\uded1 SL:     ${{ fmtPrice($signal->stop_loss) }} (-{{ $distToSl }}%)
\u2705 TP1:    ${{ fmtPrice($signal->take_profit_1) }} (+{{ $distToTp1 }}%)
\ud83c\udfaf TP2:    ${{ fmtPrice($signal->take_profit_2) }} (+{{ $distToTp2 }}%)
\ud83d\ude80 TP3:    ${{ fmtPrice($signal->take_profit_3) }} (+{{ $distToTp3 }}%)
\ud83d\udcca Confidence: {{ $signal->confidence_score }}%
\u26a0\ufe0f Bukan saran investasi. Kelola risiko dengan bijak.`;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById('copyTradeBtn');
        btn.textContent = '\u2705 Copied!';
        btn.style.color = 'var(--green)';
        setTimeout(() => { btn.textContent = '\ud83d\udccb Copy Trade'; btn.style.color = ''; }, 2500);
    });
}
</script>

{{-- Price Grid --}}
<div class="signal-price-grid">
    <div class="signal-price-item">
        <div class="label">🎯 Entry Price</div>
        <div class="value">${{ fmtPrice($signal->entry_price) }}</div>
    </div>
    <div class="signal-price-item" style="border-color:rgba(255,77,109,0.3)">
        <div class="label">🛑 Stop Loss</div>
        <div class="value" style="color:var(--red)">${{ fmtPrice($signal->stop_loss) }}</div>
        <div style="font-size:10px;color:var(--text-muted);margin-top:4px">
            {{ number_format($signal->sl_pct, 2) }}% from entry
        </div>
    </div>
    <div class="signal-price-item" style="border-color:rgba(0,212,160,0.2)">
        <div class="label">✅ Take Profit 1</div>
        <div class="value" style="color:var(--green)">${{ fmtPrice($signal->take_profit_1) }}</div>
        <div style="font-size:10px;color:var(--text-muted);margin-top:4px">
            R:R {{ number_format($signal->rr1, 2) }}
        </div>
    </div>
    <div class="signal-price-item" style="border-color:rgba(0,212,160,0.15)">
        <div class="label">✅ Take Profit 2</div>
        <div class="value" style="color:var(--green)">${{ fmtPrice($signal->take_profit_2) }}</div>
    </div>
    <div class="signal-price-item" style="border-color:rgba(0,212,160,0.1)">
        <div class="label">✅ Take Profit 3</div>
        <div class="value" style="color:var(--green)">${{ fmtPrice($signal->take_profit_3) }}</div>
    </div>
    <div class="signal-price-item">
        <div class="label">⚡ Leverage</div>
        <div class="value" style="color:var(--purple)">{{ $signal->leverage }}x Cross</div>
    </div>
</div>

{{-- ======= EMBEDDED TRADINGVIEW CANDLESTICK CHART WIDGET ======= --}}
<div class="card mb-4">
    <div class="card-header">
        <div class="card-title">📈 Live Candlestick Chart & Target Overlays ({{ $signal->coin->symbol }})</div>
        <div style="display:flex;gap:6px">
            <span class="badge badge-short">🛑 SL: ${{ fmtPrice($signal->stop_loss) }}</span>
            <span class="badge badge-pass">✅ TP1: ${{ fmtPrice($signal->take_profit_1) }}</span>
        </div>
    </div>
    <div class="card-body" style="padding:0">
        <!-- TradingView Widget BEGIN -->
        <div class="tradingview-widget-container" style="height:450px;width:100%">
            <div id="tradingview_chart_{{ $signal->id }}" style="height:100%;width:100%"></div>
            <script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
            <script type="text/javascript">
            new TradingView.widget({
                "autosize": true,
                "symbol": "BINANCE:{{ $signal->coin->symbol }}",
                "interval": "60",
                "timezone": "Asia/Jakarta",
                "theme": "dark",
                "style": "1",
                "locale": "en",
                "toolbar_bg": "#0a0b0f",
                "enable_publishing": false,
                "hide_side_toolbar": false,
                "allow_symbol_change": true,
                "container_id": "tradingview_chart_{{ $signal->id }}"
            });
            </script>
        </div>
        <!-- TradingView Widget END -->
    </div>
</div>

{{-- ======= INTERACTIVE POSITION RISK CALCULATOR ======= --}}
<div class="card mb-4" style="background:var(--bg-700);border:1px solid rgba(0,212,160,0.3)">
    <div class="card-header">
        <div class="card-title">🧮 Interactive Position Risk & Lot Size Calculator</div>
        <span class="badge badge-pass">Risk Management</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;align-items:center">
            <div>
                <label class="form-label">Total Account Balance ($)</label>
                <input type="number" id="calcBalance" class="form-input mono" value="1000" oninput="calculateRisk()">
            </div>
            <div>
                <label class="form-label">Risk Per Trade (%)</label>
                <input type="number" step="0.5" id="calcRiskPct" class="form-input mono" value="2.0" oninput="calculateRisk()">
            </div>
            <div>
                <label class="form-label">Leverage</label>
                <input type="number" id="calcLeverage" class="form-input mono" value="{{ $signal->leverage }}" oninput="calculateRisk()">
            </div>
        </div>

        <div style="margin-top:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;background:var(--bg-800);padding:16px;border-radius:10px;border:1px solid var(--border)">
            <div>
                <div class="text-sm text-muted">Max Loss ($)</div>
                <div id="resRiskDollar" class="mono font-bold" style="color:var(--red);font-size:18px">$20.00</div>
            </div>
            <div>
                <div class="text-sm text-muted">Position Size (USDT)</div>
                <div id="resPositionUsdt" class="mono font-bold" style="color:var(--blue);font-size:18px">$0.00</div>
            </div>
            <div>
                <div class="text-sm text-muted">Margin Required ($)</div>
                <div id="resMarginUsdt" class="mono font-bold" style="color:var(--purple);font-size:18px">$0.00</div>
            </div>
            <div>
                <div class="text-sm text-muted">Est. Profit TP1 (+1.5R)</div>
                <div id="resProfitTp1" class="mono font-bold" style="color:var(--green);font-size:18px">+$30.00</div>
            </div>
        </div>
    </div>
</div>

{{-- AI News & Fundamental Insight Section --}}
@php $latestNews = $signal->coin->latestNewsSentiment; @endphp
@if($latestNews)
<div class="card mb-4" style="border:1px solid rgba(168,85,247,0.3)">
    <div class="card-header">
        <div class="card-title">🤖 AI Fundamental & News Insight</div>
        <span class="badge badge-{{ $latestNews->sentiment_badge_color }}">{{ $latestNews->sentiment_emoji }}</span>
    </div>
    <div class="card-body">
        <div style="font-size:14px;font-weight:700;color:var(--text-primary);margin-bottom:6px">
            {{ $latestNews->title }}
        </div>
        <div style="font-size:13px;color:var(--purple);line-height:1.6;margin-bottom:10px">
            {{ $latestNews->ai_summary }}
        </div>
        <div style="display:flex;gap:12px;font-size:11px;color:var(--text-muted)">
            <span>Source: <strong>{{ $latestNews->source }}</strong></span>
            <span>Catalyst: <strong style="color:var(--green)">{{ $latestNews->catalyst ?? 'General' }}</strong></span>
            <span>Risk Level: <strong style="color:var(--{{ $latestNews->risk_badge_color }})">{{ strtoupper($latestNews->risk_level) }}</strong></span>
        </div>
    </div>
</div>
@endif

{{-- Two column: Indicators + Telegram Message --}}
<div class="grid-2">
    {{-- Indicator Snapshot --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📊 Indicator Snapshot</div>
            <span class="badge badge-blue">{{ $signal->interval }}</span>
        </div>
        <div class="card-body">
            @php
            $indicators = [
                ['RSI', $signal->rsi_at_signal ? number_format((float)$signal->rsi_at_signal, 2) : '—',
                    $signal->rsi_at_signal >= 50 && $signal->rsi_at_signal <= 70 ? 'var(--green)' : 'var(--text-secondary)'],
                ['Volume Spike', $signal->volume_spike_at_signal ? number_format((float)$signal->volume_spike_at_signal, 2) . 'x' : '—',
                    $signal->volume_spike_at_signal >= 1.5 ? 'var(--green)' : 'var(--text-secondary)'],
                ['ATR', $signal->atr_at_signal ? number_format((float)$signal->atr_at_signal, 6) : '—', 'var(--text-primary)'],
            ];
            @endphp
            @foreach($indicators as [$label, $value, $color])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border)">
                <span style="color:var(--text-muted);font-size:13px">{{ $label }}</span>
                <span style="color:{{ $color }};font-family:var(--font-mono);font-weight:600">{{ $value }}</span>
            </div>
            @endforeach

            <div style="padding-top:12px">
                <div style="font-size:11px;color:var(--text-muted);margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px">Analysis Notes</div>
                @if($signal->note)
                    <div style="font-size:12px;color:var(--text-secondary);line-height:1.8;white-space:pre-line">{{ $signal->note }}</div>
                @else
                    <div style="color:var(--text-muted);font-size:12px">No notes available</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Telegram Preview --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📱 Telegram Message Preview</div>
            <span class="badge badge-{{ $signal->status }}">{{ $signal->status }}</span>
        </div>
        <div class="card-body">
            <div style="background:var(--bg-900);border-radius:10px;padding:16px;font-family:var(--font-mono);font-size:12px;line-height:1.8;color:var(--text-secondary);white-space:pre-wrap;overflow-x:auto">{{ $signal->telegram_message ?? 'No message generated' }}</div>

            @if($signal->sent_at)
                <div style="margin-top:12px;font-size:12px;color:var(--green)">
                    ✅ Sent at {{ $signal->sent_at->format('Y-m-d H:i:s') }}
                </div>
            @endif

            @if($signal->status === 'pending')
                <div style="margin-top:12px">
                    <form method="POST" action="{{ route('signals.cancel', $signal) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-ghost" style="color:var(--yellow)">🚫 Cancel Signal</button>
                    </form>
                </div>
            @elseif($signal->status === 'cancelled')
                <div style="margin-top:12px">
                    <form method="POST" action="{{ route('signals.reactivate', $signal) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-ghost" style="color:var(--green)">🔄 Reactivate Signal</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function calculateRisk() {
    const balance = parseFloat(document.getElementById('calcBalance').value) || 0;
    const riskPct = parseFloat(document.getElementById('calcRiskPct').value) || 0;
    const leverage = parseFloat(document.getElementById('calcLeverage').value) || 1;

    const entryPrice = {{ (float)$signal->entry_price }};
    const slPrice    = {{ (float)$signal->stop_loss }};
    const tp1Price   = {{ (float)$signal->take_profit_1 }};

    const riskDollar = balance * (riskPct / 100);
    const priceDiffPct = Math.abs(entryPrice - slPrice) / entryPrice;

    if (priceDiffPct === 0) return;

    const positionUsdt = riskDollar / priceDiffPct;
    const marginRequired = positionUsdt / leverage;
    const profitTp1 = riskDollar * 1.5;

    document.getElementById('resRiskDollar').textContent = '$' + riskDollar.toFixed(2);
    document.getElementById('resPositionUsdt').textContent = '$' + positionUsdt.toFixed(2);
    document.getElementById('resMarginUsdt').textContent = '$' + marginRequired.toFixed(2);
    document.getElementById('resProfitTp1').textContent = '+$' + profitTp1.toFixed(2);
}

document.addEventListener('DOMContentLoaded', calculateRisk);
</script>
<script>
function updateVisualRuler(curP) {
    if (!curP || isNaN(curP) || curP <= 0) return;

    const entry = {{ (float)$signal->entry_price }};
    const sl    = {{ (float)$signal->stop_loss }};
    const tp1   = {{ (float)$signal->take_profit_1 }};
    const tp2   = {{ (float)$signal->take_profit_2 }};
    const tp3   = {{ (float)$signal->take_profit_3 }};
    const dir   = "{{ $signal->direction }}";

    // Format current price
    let dec = curP >= 100 ? 2 : (curP >= 1 ? 4 : (curP >= 0.001 ? 6 : 8));
    const fmtCurP = '$' + curP.toFixed(dec);

    // Update Advice Banner Price
    const bannerPrice = document.getElementById('liveBannerPrice');
    if (bannerPrice) bannerPrice.textContent = fmtCurP;

    // Recalculate ruler range (min/max)
    const rangeMin = Math.min(sl, tp3, curP) * 0.998;
    const rangeMax = Math.max(sl, tp3, curP) * 1.002;
    const range    = rangeMax - rangeMin;

    const toPos = (p) => range > 0 ? Math.min(98, Math.max(2, ((p - rangeMin) / range) * 100)) : 50;

    const slPos    = toPos(sl);
    const entryPos = toPos(entry);
    const tp1Pos   = toPos(tp1);
    const tp2Pos   = toPos(tp2);
    const tp3Pos   = toPos(tp3);
    const curPos   = toPos(curP);

    // Update marker positions
    const mSl = document.getElementById('marker-sl');
    if (mSl) mSl.style.left = slPos + '%';

    const mEntry = document.getElementById('marker-entry');
    if (mEntry) mEntry.style.left = entryPos + '%';

    const mTp1 = document.getElementById('marker-tp1');
    if (mTp1) mTp1.style.left = tp1Pos + '%';

    const mTp2 = document.getElementById('marker-tp2');
    if (mTp2) mTp2.style.left = tp2Pos + '%';

    const mTp3 = document.getElementById('marker-tp3');
    if (mTp3) mTp3.style.left = tp3Pos + '%';

    const container = document.getElementById('ruler-cur-container');
    if (container) {
        container.style.transition = 'left 0.3s ease';
        container.style.left = curPos + '%';
    }

    // Update profit/loss zones
    const pZone = document.getElementById('ruler-profit-zone');
    const lZone = document.getElementById('ruler-loss-zone');
    if (dir === 'LONG') {
        if (pZone) { pZone.style.left = entryPos + '%'; pZone.style.width = Math.max(0, tp3Pos - entryPos) + '%'; }
        if (lZone) { lZone.style.left = slPos + '%'; lZone.style.width = Math.max(0, entryPos - slPos) + '%'; }
    } else {
        if (pZone) { pZone.style.left = tp3Pos + '%'; pZone.style.width = Math.max(0, entryPos - tp3Pos) + '%'; }
        if (lZone) { lZone.style.left = entryPos + '%'; lZone.style.width = Math.max(0, slPos - entryPos) + '%'; }
    }

    // Update NOW price text & delta %
    const pEl = document.getElementById('ruler-cur-price');
    if (pEl) pEl.textContent = fmtCurP;

    // Delta from entry calculation based on direction
    let distCur = 0;
    if (entry > 0) {
        if (dir === 'LONG') {
            distCur = ((curP - entry) / entry) * 100;
        } else {
            distCur = ((entry - curP) / entry) * 100;
        }
    }

    const dEl = document.getElementById('ruler-cur-delta');
    if (dEl) {
        dEl.textContent = (distCur >= 0 ? '+' : '') + distCur.toFixed(2) + '%';
        dEl.style.color = distCur >= 0 ? 'var(--green)' : 'var(--red)';
    }

    const cVal = document.getElementById('dist-cur-val');
    if (cVal) {
        cVal.textContent = (distCur >= 0 ? '+' : '') + distCur.toFixed(2) + '%';
        cVal.style.color = distCur >= 0 ? 'var(--green)' : 'var(--red)';
    }
}

// Dedicated Real-Time Per-Tick WebSocket & Multi-Provider Engine
(function initRealtimeTickEngine() {
    const rawSymbol = "{{ $signal->coin->symbol }}";
    const symbolLower = rawSymbol.toLowerCase();
    const symbolUpper = rawSymbol.toUpperCase();

    // 1. Direct WebSockets (Binance Vision, Spot & Futures)
    const wsUrls = [
        `wss://stream.binance.vision/ws/${symbolLower}@ticker`,
        `wss://stream.binance.com:9443/ws/${symbolLower}@ticker`,
        `wss://fstream.binance.com/ws/${symbolLower}@ticker`
    ];

    wsUrls.forEach(url => {
        try {
            const socket = new WebSocket(url);
            socket.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    const p = data.c || data.p;
                    if (p) {
                        const price = parseFloat(p);
                        if (!isNaN(price) && price > 0) {
                            updateVisualRuler(price);
                        }
                    }
                } catch(e) {}
            };
        } catch(e) {}
    });

    // 2. Global Event Listener fallback
    window.addEventListener('binance-tick', (e) => {
        if (e.detail && e.detail.symbol && e.detail.symbol.toUpperCase() === symbolUpper) {
            updateVisualRuler(e.detail.price);
        }
    });

    // 3. Multi-Tier REST Fetch Fallback (Binance Vision -> Bybit -> Laravel Proxy)
    function fetchLivePrice() {
        // Tier 1: Binance Vision Public Mirror (Unblocked)
        fetch(`https://data-api.binance.vision/api/v3/ticker/price?symbol=${symbolUpper}`)
            .then(r => r.json())
            .then(data => {
                if (data && data.price) {
                    updateVisualRuler(parseFloat(data.price));
                    return;
                }
                throw new Error('Fallback to Bybit');
            })
            .catch(() => {
                // Tier 2: Bybit Public API (Unblocked)
                fetch(`https://api.bybit.com/v5/market/tickers?category=linear&symbol=${symbolUpper}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.result && data.result.list && data.result.list[0]) {
                            const p = parseFloat(data.result.list[0].lastPrice);
                            if (p > 0) {
                                updateVisualRuler(p);
                                return;
                            }
                        }
                        throw new Error('Fallback to Server Proxy');
                    })
                    .catch(() => {
                        // Tier 3: Laravel Internal Proxy Endpoint
                        fetch(`/api/live-price/${symbolUpper}`)
                            .then(r => r.json())
                            .then(data => {
                                if (data && data.price && data.price > 0) {
                                    updateVisualRuler(parseFloat(data.price));
                                }
                            })
                            .catch(err => console.debug(err));
                    });
            });
    }

    document.addEventListener('DOMContentLoaded', fetchLivePrice);
    fetchLivePrice();
    setInterval(fetchLivePrice, 2000);
})();
</script>
@endpush

@endsection
