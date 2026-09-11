@extends('layouts.app')

@section('title', 'Backtest Engine')
@section('page-title', '📊 Dynamic Backtest Engine')
@section('page-subtitle', 'Historical strategy simulation & multi-coin quantitative analysis')

@section('content')

{{-- ======= FILTER & SIMULATION CONTROL BAR ======= --}}
<div class="card mb-4" style="background:var(--bg-700);border:1px solid rgba(102,126,234,0.4)">
    <div class="card-header">
        <div class="card-title">🎛️ Backtest Configuration & Filters</div>
        <span class="badge badge-purple">Quantitative Strategy Engine</span>
    </div>
    <div class="card-body">
        <form id="backtestForm" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;align-items:end">
            <div>
                <label style="font-size:12px;color:var(--text-muted);font-weight:600;display:block;margin-bottom:6px">Coin Filter Criteria</label>
                <select name="filter_type" id="filterType" class="form-select" onchange="toggleCoinSelector()">
                    <option value="under_10" selected>💸 Coins Under $10 (< $10)</option>
                    <option value="under_1">🪙 Coins Under $1 (< $1)</option>
                    <option value="all">🌐 All Monitored Coins</option>
                    <option value="coin">🎯 Specific Coin Only</option>
                </select>
            </div>

            <div id="coinSelectContainer" style="display:none">
                <label style="font-size:12px;color:var(--text-muted);font-weight:600;display:block;margin-bottom:6px">Select Specific Coin</label>
                <select name="symbol" id="symbolSelect" class="form-select">
                    @foreach($coins as $c)
                        <option value="{{ $c->symbol }}">{{ $c->base_asset }} / USDT (${{ fmtPrice($c->last_price) }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="font-size:12px;color:var(--text-muted);font-weight:600;display:block;margin-bottom:6px">Historical Period</label>
                <select name="days" class="form-select">
                    <option value="7">📅 Last 7 Days</option>
                    <option value="14">📅 Last 14 Days</option>
                    <option value="30" selected>📅 Last 30 Days</option>
                    <option value="90">📅 Last 90 Days</option>
                </select>
            </div>

            <div>
                <label style="font-size:12px;color:var(--text-muted);font-weight:600;display:block;margin-bottom:6px">Candle Timeframe</label>
                <select name="interval" class="form-select">
                    <option value="15m">⏱️ 15 Minutes (15m)</option>
                    <option value="1h" selected>⏱️ 1 Hour (1h)</option>
                    <option value="4h">⏱️ 4 Hours (4h)</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-primary w-full" id="runBacktestBtn" style="height:42px;font-weight:700">
                    <span id="btnIcon">🚀</span> <span id="btnText">Run Backtest Simulation</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ======= BACKTEST RESULTS DASHBOARD ======= --}}
<div id="resultsContainer" style="display:none">
    
    {{-- Metric Cards --}}
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon purple">📈</div>
            <div class="stat-info">
                <div class="stat-label">Simulated Win Rate</div>
                <div class="stat-value" id="resWinRate">0%</div>
                <div class="stat-change text-muted" id="resWinLossRatio">0 Wins / 0 Losses</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div class="stat-info">
                <div class="stat-label">Net Profit (Risk Units)</div>
                <div class="stat-value" id="resNetR">+0.0 R</div>
                <div class="stat-change text-muted">Accumulated Risk Reward</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue">🎯</div>
            <div class="stat-info">
                <div class="stat-label">Total Trades Simulated</div>
                <div class="stat-value" id="resTotalTrades">0</div>
                <div class="stat-change text-muted" id="resCoinsCount">0 coins analyzed</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon yellow">⚖️</div>
            <div class="stat-info">
                <div class="stat-label">Profit Factor</div>
                <div class="stat-value" id="resProfitFactor">0.0</div>
                <div class="stat-change text-muted">Gross Win / Gross Loss</div>
            </div>
        </div>
    </div>

    {{-- Equity Curve & Outcome Breakdown Grid --}}
    <div class="grid-2 mb-4">
        
        {{-- Equity Curve Chart --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">📈 Simulated Cumulative Equity Curve (Net R Return)</div>
            </div>
            <div class="card-body">
                <div style="height:260px;width:100%;position:relative" id="equityChartWrapper">
                    <canvas id="equityChartCanvas" style="width:100%;height:100%"></canvas>
                </div>
            </div>
        </div>

        {{-- Outcome Distribution Breakdown --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">🎯 Backtest Outcome Distribution</div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;justify-content:center;gap:14px;height:260px">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--bg-800);border-radius:8px">
                    <span style="color:var(--green);font-weight:600">🚀 Hit TP3 (+4.0R)</span>
                    <span class="mono font-bold" id="resHitTp3" style="font-size:16px">0</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--bg-800);border-radius:8px">
                    <span style="color:var(--green);font-weight:600">🎯 Hit TP2 (+2.5R)</span>
                    <span class="mono font-bold" id="resHitTp2" style="font-size:16px">0</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--bg-800);border-radius:8px">
                    <span style="color:var(--green);font-weight:600">✅ Hit TP1 (+1.5R)</span>
                    <span class="mono font-bold" id="resHitTp1" style="font-size:16px">0</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--bg-800);border-radius:8px">
                    <span style="color:var(--red);font-weight:600">🛑 Hit SL (-1.0R)</span>
                    <span class="mono font-bold" id="resHitSl" style="font-size:16px">0</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Simulated Trades Journal Table --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📜 Simulated Historical Trades Journal (Last 50 Executions)</div>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>Coin</th>
                            <th>Direction</th>
                            <th>Entry Price</th>
                            <th>Stop Loss</th>
                            <th>TP1 Target</th>
                            <th>Outcome</th>
                            <th>PnL (R)</th>
                        </tr>
                    </thead>
                    <tbody id="tradesTableBody">
                        {{-- AJAX injected --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Empty State Initial --}}
<div id="initialState" class="card" style="padding:60px 20px;text-align:center">
    <div style="font-size:48px;margin-bottom:12px;opacity:0.6">📊</div>
    <h3 style="font-size:18px;font-weight:700;color:var(--text-primary);margin-bottom:8px">Ready to Backtest</h3>
    <p style="font-size:13px;color:var(--text-secondary);max-width:500px;margin:0 auto 20px">
        Select your filter criteria above (e.g. <strong>Coins Under $10</strong>) and click <strong>Run Backtest Simulation</strong> to simulate strategy performance on historical candles.
    </p>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleCoinSelector() {
    const val = document.getElementById('filterType').value;
    const container = document.getElementById('coinSelectContainer');
    container.style.display = (val === 'coin') ? 'block' : 'none';
}

let chartInstance = null;

document.getElementById('backtestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('runBacktestBtn');
    const icon = document.getElementById('btnIcon');
    const text = document.getElementById('btnText');

    btn.disabled = true;
    icon.innerHTML = '<span class="spinner"></span>';
    text.textContent = 'Simulating...';

    const formData = new FormData(this);
    const payload = {
        filter_type: formData.get('filter_type'),
        symbol: formData.get('symbol'),
        days: parseInt(formData.get('days')),
        interval: formData.get('interval'),
    };

    fetch('{{ route("backtest.run") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderResults(data);
            showFlash(`✅ Backtest complete! ${data.total_trades} trades simulated across ${data.coins_count} coins.`, 'success');
        } else {
            showFlash('❌ ' + (data.message || 'Backtest failed'), 'error');
        }
    })
    .catch(err => showFlash('❌ Network error: ' + err.message, 'error'))
    .finally(() => {
        btn.disabled = false;
        icon.textContent = '🚀';
        text.textContent = 'Run Backtest Simulation';
    });
});

function fmtPriceJS(v) {
    if (v >= 100)    return v.toFixed(2);
    if (v >= 1)      return v.toFixed(4);
    if (v >= 0.001)  return v.toFixed(6);
    return v.toFixed(8);
}

function renderResults(data) {
    document.getElementById('initialState').style.display = 'none';
    document.getElementById('resultsContainer').style.display = 'block';

    document.getElementById('resWinRate').textContent = `${data.win_rate_pct}%`;
    document.getElementById('resWinRate').style.color = data.win_rate_pct >= 50 ? 'var(--green)' : 'var(--red)';
    document.getElementById('resWinLossRatio').textContent = `${data.win_trades} Wins / ${data.loss_trades} Losses`;
    
    document.getElementById('resNetR').textContent = `${data.net_profit_r >= 0 ? '+' : ''}${data.net_profit_r} R`;
    document.getElementById('resNetR').style.color = data.net_profit_r >= 0 ? 'var(--green)' : 'var(--red)';
    
    document.getElementById('resTotalTrades').textContent = data.total_trades;
    document.getElementById('resCoinsCount').textContent = `${data.coins_count} coins analyzed`;

    document.getElementById('resProfitFactor').textContent = data.profit_factor;
    document.getElementById('resProfitFactor').style.color = data.profit_factor >= 1.5 ? 'var(--green)' : 'var(--yellow)';

    document.getElementById('resHitTp3').textContent = data.hit_tp3;
    document.getElementById('resHitTp2').textContent = data.hit_tp2;
    document.getElementById('resHitTp1').textContent = data.hit_tp1;
    document.getElementById('resHitSl').textContent = data.hit_sl;

    // Render Equity Chart
    renderChart(data.equity_curve);

    // Render Trades Table
    const tbody = document.getElementById('tradesTableBody');
    tbody.innerHTML = '';

    if (data.trades.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:var(--text-muted)">No simulated trades triggered during this period.</td></tr>';
        return;
    }

    data.trades.reverse().forEach(t => {
        const tr = document.createElement('tr');
        const dirBadge = t.direction === 'LONG' ? 'badge-long' : 'badge-short';
        const pnlColor = t.pnl_r >= 0 ? 'var(--green)' : 'var(--red)';
        
        let outcomeBadge = 'badge-blue';
        if (t.outcome === 'hit_tp3') outcomeBadge = 'badge-pass';
        else if (t.outcome === 'hit_tp2') outcomeBadge = 'badge-pass';
        else if (t.outcome === 'hit_tp1') outcomeBadge = 'badge-pass';
        else if (t.outcome === 'hit_sl') outcomeBadge = 'badge-short';

        tr.innerHTML = `
            <td class="mono text-muted" style="font-size:11px">${t.timestamp}</td>
            <td style="font-weight:700;color:var(--blue)">${t.base_asset}<span style="color:var(--text-muted);font-size:11px">/USDT</span></td>
            <td><span class="badge ${dirBadge}">${t.direction}</span></td>
            <td class="mono">$${fmtPriceJS(t.entry_price)}</td>
            <td class="mono" style="color:var(--red)">$${fmtPriceJS(t.stop_loss)}</td>
            <td class="mono" style="color:var(--green)">$${fmtPriceJS(t.tp1)}</td>
            <td><span class="badge ${outcomeBadge}">${t.outcome.replace('hit_', 'HIT ').replace('_', ' ').toUpperCase()}</span></td>
            <td class="mono font-bold" style="color:${pnlColor}">${t.pnl_r >= 0 ? '+' : ''}${t.pnl_r} R</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderChart(curveData) {
    const ctx = document.getElementById('equityChartCanvas').getContext('2d');
    if (chartInstance) chartInstance.destroy();

    const labels = curveData.map(d => d.time);
    const values = curveData.map(d => d.pnl_r);

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cumulative Net Profit (R)',
                data: values,
                borderColor: '#00d4a0',
                backgroundColor: 'rgba(0, 212, 160, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#8892a4', maxTicksLimit: 8, font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#8892a4', font: { size: 10 } }
                }
            }
        }
    });
}

// Automatically run initial backtest for 'Coins Under $10' on page load
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('backtestForm').dispatchEvent(new Event('submit'));
});
</script>
@endpush
@endsection
