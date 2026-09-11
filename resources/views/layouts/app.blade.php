<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Crypto Signal Scanner</title>
    <meta name="description" content="Crypto Signal Scanner — Automated crypto trading signal bot with Binance integration">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📡</text></svg>">
</head>
<body>
<div id="app-layout">

    {{-- ========== SIDEBAR ========== --}}
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">📡</div>
            <div class="sidebar-logo-text">
                <span class="brand">CryptoSignal</span>
                <span class="tagline">Scanner Bot v1.0</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Main</div>

            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-icon">🏠</span> Dashboard
            </a>
            <a href="{{ route('scalping.index') }}" class="nav-item {{ request()->routeIs('scalping.*') ? 'active' : '' }}">
                <span class="nav-icon">⚡</span> 15M Scalper
                <span class="nav-badge" style="background:var(--purple);color:#fff">HOT</span>
            </a>
            <a href="{{ route('trail-test.index') }}" class="nav-item {{ request()->routeIs('trail-test.*') ? 'active' : '' }}">
                <span class="nav-icon">🧪</span> Trail Test
                <span class="nav-badge" style="background:var(--blue);color:#fff">NEW</span>
            </a>
            <a href="{{ route('orders.index') }}" class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <span class="nav-icon">📜</span> Order History
            </a>
            <a href="{{ route('signals.index') }}" class="nav-item {{ request()->routeIs('signals.*') ? 'active' : '' }}">
                <span class="nav-icon">⚡</span> Signals
                @php $pending = \App\Models\Signal::pending()->count(); @endphp
                @if($pending > 0)
                    <span class="nav-badge">{{ $pending }}</span>
                @endif
            </a>
            <a href="{{ route('coins.index') }}" class="nav-item {{ request()->routeIs('coins.*') ? 'active' : '' }}">
                <span class="nav-icon">🪙</span> Coins
            </a>
            <a href="{{ route('backtest.index') }}" class="nav-item {{ request()->routeIs('backtest.*') ? 'active' : '' }}">
                <span class="nav-icon">📊</span> Backtest Engine
            </a>

            <div class="nav-section-title">Monitoring</div>

            <a href="{{ route('logs.index') }}" class="nav-item {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                <span class="nav-icon">📋</span> Scan Logs
            </a>

            <div class="nav-section-title">Config</div>

            <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <span class="nav-icon">⚙️</span> Settings
            </a>
        </nav>

        <div class="sidebar-footer">
            @php $telegramCfg = !empty(config('scanner.telegram.bot_token')); @endphp
            <div class="telegram-status">
                <div class="dot {{ $telegramCfg ? 'online' : 'offline' }}"></div>
                Telegram {{ $telegramCfg ? 'Connected' : 'Not configured' }}
            </div>
            <div style="margin-top:4px">
                DB: SQLite &bull; Laravel {{ app()->version() }}
            </div>
        </div>
    </aside>

    {{-- ========== MAIN CONTENT ========== --}}
    <div class="main-content">

        {{-- Top Running Live Price Ticker Bar (Ultra-Premium Binance Real-Time Stream) --}}
        @php
            $topTickerCoins = \App\Models\Coin::active()->whereIn('symbol', ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'BNBUSDT', 'DOGEUSDT', 'PEPEUSDT'])->with('latestIndicator')->get();
            $icons = ['BTCUSDT' => '🟠', 'ETHUSDT' => '🔷', 'SOLUSDT' => '🟣', 'BNBUSDT' => '🟡', 'DOGEUSDT' => '🐕', 'PEPEUSDT' => '🐸'];
        @endphp
        <div class="ticker-container">
            <div class="ticker-badge-live">
                <span class="live-pulse-dot"></span>
                <span>BINANCE LIVE RADAR</span>
            </div>
            @foreach($topTickerCoins as $tCoin)
                @php 
                    $tInd = $tCoin->latestIndicator; 
                    $pct = $tInd ? (float)$tInd->price_change_pct : 0;
                    $icon = $icons[$tCoin->symbol] ?? '🪙';
                @endphp
                <div id="ticker-item-{{ strtolower($tCoin->symbol) }}" class="ticker-pill-card">
                    <span>{{ $icon }}</span>
                    <span class="ticker-coin-tag">{{ $tCoin->base_asset }}</span>
                    <span class="ticker-price-val">$<span id="ticker-price-{{ strtolower($tCoin->symbol) }}">{{ fmtPrice($tCoin->last_price) }}</span></span>
                    <span id="ticker-change-{{ strtolower($tCoin->symbol) }}" class="ticker-change-badge {{ $pct >= 0 ? 'up' : 'down' }}">
                        {{ $pct >= 0 ? '▲ +' : '▼ ' }}{{ number_format(abs($pct), 1) }}%
                    </span>
                </div>
            @endforeach
        </div>

        {{-- Topbar --}}
        <header class="topbar">
            <div>
                <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
                <div class="topbar-subtitle">@yield('page-subtitle', 'Crypto Signal Scanner Bot')</div>
            </div>

            <div class="topbar-actions">
                @yield('topbar-actions')

                {{-- Command Palette Spotlight Trigger Button --}}
                <button class="btn btn-ghost" onclick="toggleCommandPalette()" title="Open Command Palette (Ctrl+K)">
                    🔍 <span style="font-size:12px;opacity:0.8">Search (Ctrl+K)</span>
                </button>

                {{-- Sound Alert Toggle Button --}}
                <button class="btn btn-ghost" id="soundAlertBtn" onclick="toggleSoundAlert()" title="Toggle Web Audio Chime & Desktop Notifications">
                    <span id="soundAlertIcon">🔊</span>
                    <span id="soundAlertText">Sound: <strong id="soundAlertState" style="color:var(--green)">ON</strong></span>
                </button>

                {{-- Live Auto-Refresh Toggle Button --}}
                <button class="btn btn-ghost" id="autoRefreshBtn" onclick="toggleAutoRefresh()" title="Toggle 60s Real-Time Auto Refresh">
                    <span id="autoRefreshIcon">🔄</span>
                    <span id="autoRefreshText">Auto-Reload: <strong id="autoRefreshTimer" style="color:var(--green)">60s</strong></span>
                </button>

                {{-- Timeframe Interval Selector Dropdown --}}
                <select id="scannerIntervalSelect" style="background:var(--bg-800);border:1px solid var(--border);color:var(--text-primary);padding:6px 10px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;outline:none">
                    <option value="15m" {{ (request('interval') === '15m' || (isset($selectedInterval) && $selectedInterval === '15m')) ? 'selected' : '' }}>⚡ 15m (Scalp)</option>
                    <option value="1h" {{ (request('interval') === '1h' || (!request('interval') && (!isset($selectedInterval) || $selectedInterval === '1h'))) ? 'selected' : '' }}>⏰ 1h (Standard)</option>
                    <option value="4h" {{ (request('interval') === '4h' || (isset($selectedInterval) && $selectedInterval === '4h')) ? 'selected' : '' }}>📊 4h (Swing)</option>
                </select>

                <button class="btn btn-scanner" id="runScannerBtn" onclick="runScanner()">
                    <span id="scannerBtnIcon">🔍</span>
                    <span id="scannerBtnText">Run Scanner</span>
                </button>

                @auth
                <div style="display:flex;align-items:center;gap:8px;padding-left:8px;border-left:1px solid var(--border)">
                    <div style="font-size:12px;font-weight:700;color:var(--green);display:flex;align-items:center;gap:6px">
                        <span>👤</span>
                        <span>{{ Auth::user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-ghost" style="padding:4px 8px;font-size:11px;color:var(--red);border:1px solid rgba(255,77,109,0.3)" title="Logout Session">
                            🚪 Logout
                        </button>
                    </form>
                </div>
                @endauth
            </div>
        </header>

        {{-- Page Content --}}
        <main class="page-content">
            @yield('content')
        </main>
    </div>
</div>

{{-- ========== COMMAND PALETTE SPOTLIGHT MODAL (Ctrl + K) ========== --}}
<div id="cmdPaletteModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);z-index:99999;backdrop-filter:blur(6px);align-items:flex-start;justify-content:center;padding-top:10vh" onclick="if(event.target===this) toggleCommandPalette()">
    <div style="background:var(--bg-700);border:1px solid rgba(102,126,234,0.4);border-radius:14px;width:90%;max-width:580px;box-shadow:0 20px 50px rgba(0,0,0,0.6);overflow:hidden">
        <div style="padding:16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px">
            <span style="font-size:18px">🔍</span>
            <input type="text" id="cmdInput" placeholder="Type a coin (BTC, SOL, ETH) or action..." style="background:none;border:none;outline:none;color:var(--text-primary);font-size:16px;width:100%;font-family:var(--font-sans)" oninput="filterCmdList()">
            <span style="font-size:11px;background:var(--bg-900);padding:3px 6px;border-radius:4px;color:var(--text-muted);border:1px solid var(--border)">ESC</span>
        </div>
        <div id="cmdList" style="max-height:360px;overflow-y:auto;padding:8px">
            <div class="cmd-item" onclick="location.href='{{ route('dashboard') }}'">🏠 Go to Dashboard</div>
            <div class="cmd-item" onclick="location.href='{{ route('signals.index') }}'">⚡ View Signal History</div>
            <div class="cmd-item" onclick="location.href='{{ route('coins.index') }}'">🪙 View Monitored Coins</div>
            <div class="cmd-item" onclick="runScanner(); toggleCommandPalette()">🔍 Run Scanner Engine Now</div>
            <div class="cmd-item" onclick="location.href='{{ route('signals.export') }}'">📥 Export Signal Journal (CSV)</div>
            <div class="cmd-item" onclick="location.href='{{ route('settings.index') }}'">⚙️ Configure Scanner Settings</div>
        </div>
    </div>
</div>

<style>
.cmd-item {
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 14px;
    color: var(--text-primary);
    cursor: pointer;
    transition: all 0.15s ease;
}
.cmd-item:hover {
    background: var(--bg-800);
    color: var(--blue);
}
</style>

{{-- Flash Messages --}}
<div class="flash-messages" id="flashMessages">
    @if(session('success'))
        <div class="flash success" onclick="this.remove()">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flash error" onclick="this.remove()">
            ❌ {{ session('error') }}
        </div>
    @endif
</div>

<script>
// =====================
// Command Palette Spotlight (Ctrl + K)
// =====================
function toggleCommandPalette() {
    const modal = document.getElementById('cmdPaletteModal');
    const input = document.getElementById('cmdInput');
    if (modal.style.display === 'none' || !modal.style.display) {
        modal.style.display = 'flex';
        input.value = '';
        input.focus();
    } else {
        modal.style.display = 'none';
    }
}

document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        toggleCommandPalette();
    }
    if (e.key === 'Escape') {
        const modal = document.getElementById('cmdPaletteModal');
        if (modal && modal.style.display === 'flex') modal.style.display = 'none';
    }
});

function filterCmdList() {
    const val = document.getElementById('cmdInput').value.toLowerCase();
    const items = document.querySelectorAll('.cmd-item');
    items.forEach(item => {
        if (item.textContent.toLowerCase().includes(val)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// =====================
// Web Audio API Chime Synthesizer
// =====================
function playNotificationChime() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();
        
        const now = ctx.currentTime;
        const osc1 = ctx.createOscillator();
        const osc2 = ctx.createOscillator();
        const gain = ctx.createGain();

        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(587.33, now); // D5
        osc1.frequency.exponentialRampToValueAtTime(880, now + 0.15); // A5

        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(880, now + 0.15);
        osc2.frequency.exponentialRampToValueAtTime(1174.66, now + 0.3); // D6

        gain.gain.setValueAtTime(0.15, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.6);

        osc1.connect(gain);
        osc2.connect(gain);
        gain.connect(ctx.destination);

        osc1.start(now);
        osc1.stop(now + 0.3);
        osc2.start(now + 0.15);
        osc2.stop(now + 0.6);
    } catch (e) {
        console.warn('Audio chime unsupported or blocked', e);
    }
}

// =====================
// Real-Time Auto-Reload Counter (60s)
// =====================
let autoRefreshEnabled = true;
let countdown = 60;
let refreshInterval = null;

function startCountdown() {
    clearInterval(refreshInterval);
    refreshInterval = setInterval(() => {
        if (!autoRefreshEnabled) return;

        countdown--;
        const timerEl = document.getElementById('autoRefreshTimer');
        if (timerEl) {
            timerEl.textContent = `${countdown}s`;
            if (countdown <= 10) {
                timerEl.style.color = 'var(--yellow)';
            } else {
                timerEl.style.color = 'var(--green)';
            }
        }

        if (countdown <= 0) {
            clearInterval(refreshInterval);
            if (timerEl) timerEl.textContent = 'Refreshing...';
            location.reload();
        }
    }, 1000);
}

function toggleAutoRefresh() {
    autoRefreshEnabled = !autoRefreshEnabled;
    const icon = document.getElementById('autoRefreshIcon');
    const timerEl = document.getElementById('autoRefreshTimer');
    const textEl = document.getElementById('autoRefreshText');

    if (autoRefreshEnabled) {
        countdown = 60;
        if (timerEl) timerEl.textContent = '60s';
        textEl.style.opacity = '1';
        startCountdown();
        showFlash('🔄 Auto-refresh resume (60s)', 'success');
    } else {
        clearInterval(refreshInterval);
        if (timerEl) timerEl.textContent = 'PAUSED';
        timerEl.style.color = 'var(--text-muted)';
        textEl.style.opacity = '0.7';
        showFlash('⏸️ Auto-refresh paused', 'info');
    }
}

// Start countdown on page load
document.addEventListener('DOMContentLoaded', startCountdown);

// =====================
// Run Scanner via AJAX
// =====================
function runScanner() {
    const btn  = document.getElementById('runScannerBtn');
    const icon = document.getElementById('scannerBtnIcon');
    const text = document.getElementById('scannerBtnText');
    const sel  = document.getElementById('scannerIntervalSelect');
    const interval = sel ? sel.value : '15m';

    btn.disabled = true;
    icon.innerHTML = '<span class="spinner"></span>';
    text.textContent = 'Scanning ' + interval + '...';

    fetch('{{ route("scanner.run") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ interval: interval })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const s = data.stats;
            playNotificationChime();
            showFlash(`✅ Scan done! ${s.signals_generated} signals generated, ${s.coins_passed_screen}/${s.coins_total} coins passed filter. (${s.duration_seconds}s)`, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showFlash('❌ Scanner error: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(err => showFlash('❌ Network error: ' + err.message, 'error'))
    .finally(() => {
        btn.disabled = false;
        icon.textContent = '🔍';
        text.textContent = 'Run Scanner';
    });
}

function showFlash(msg, type) {
    const el = document.createElement('div');
    el.className = `flash ${type}`;
    el.textContent = msg;
    el.onclick = () => el.remove();
    document.getElementById('flashMessages').appendChild(el);
    setTimeout(() => el.remove(), 5000);
}

document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => el.remove(), 4000);
});

// =====================
// Binance Live Global Per-Tick WebSocket Stream & Auto Sync Engine
// =====================
(function connectBinanceGlobalWS() {
    const wsUrl = "wss://stream.binance.com:9443/ws/!ticker@arr";
    let pendingSync = {};

    // Sync prices to Laravel backend every 5 seconds
    setInterval(() => {
        const payload = Object.values(pendingSync);
        if (payload.length === 0) return;
        pendingSync = {};

        fetch('{{ route("api.sync-prices") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ prices: payload })
        }).catch(err => console.debug('Sync prices error:', err));
    }, 5000);

    try {
        const ws = new WebSocket(wsUrl);
        ws.onmessage = (event) => {
            const tickers = JSON.parse(event.data);
            if (!Array.isArray(tickers)) return;

            tickers.forEach(t => {
                const symbol = t.s; // e.g. BTCUSDT
                const symbolLower = t.s.toLowerCase();
                const price = parseFloat(t.c);
                const changePct = parseFloat(t.P);
                const volume = parseFloat(t.q);

                if (!symbol.endsWith('USDT') || isNaN(price) || price <= 0) return;

                pendingSync[symbolLower] = { symbol: symbol, price: price, volume: volume, change_pct: changePct };

                let decimals = price >= 100 ? 2 : (price >= 1 ? 4 : (price >= 0.001 ? 6 : 8));
                const fmtPriceStr = '$' + price.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });

                // 1. Update Ticker Bar at Top of Page
                const elPrice = document.getElementById(`ticker-price-${symbolLower}`);
                const elItem  = document.getElementById(`ticker-item-${symbolLower}`);
                const elChg   = document.getElementById(`ticker-change-${symbolLower}`);

                if (elPrice) {
                    const oldPrice = parseFloat(elPrice.textContent.replace(/,/g, ''));
                    elPrice.textContent = price.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });

                    if (elChg) {
                        elChg.className = `ticker-change-badge ${changePct >= 0 ? 'up' : 'down'}`;
                        elChg.textContent = `${changePct >= 0 ? '▲ +' : '▼ '}${Math.abs(changePct).toFixed(1)}%`;
                    }

                    if (elItem && !isNaN(oldPrice) && oldPrice !== price) {
                        const isUp = price > oldPrice;
                        elItem.style.background = isUp ? 'rgba(0, 212, 160, 0.35)' : 'rgba(255, 77, 109, 0.35)';
                        elItem.style.borderColor = isUp ? 'var(--green)' : 'var(--red)';
                        elItem.style.transform = 'scale(1.04)';
                        setTimeout(() => { 
                            elItem.style.background = 'var(--bg-800)'; 
                            elItem.style.borderColor = 'var(--border)';
                            elItem.style.transform = 'scale(1)';
                        }, 350);
                    }
                }

                // 2. Update Generic Live Price Elements (Binance)
                document.querySelectorAll(`[data-live-price="${symbol}"]`).forEach(el => {
                    const oldP = parseFloat(el.getAttribute('data-prev-price') || 0);
                    el.setAttribute('data-prev-price', price);
                    el.textContent = fmtPriceStr;

                    if (oldP > 0 && oldP !== price) {
                        el.style.transition = 'color 0.15s ease';
                        el.style.color = price > oldP ? 'var(--green)' : 'var(--red)';
                        setTimeout(() => el.style.color = '', 400);
                    }
                });

                // 3. Update 24h Change Elements Live
                document.querySelectorAll(`[data-live-change="${symbol}"]`).forEach(el => {
                    el.textContent = (changePct >= 0 ? '+' : '') + changePct.toFixed(2) + '%';
                    el.style.color = changePct >= 0 ? 'var(--green)' : 'var(--red)';
                });

                // 4. Update Bybit Comparison Cells Live (+0.08% spread)
                document.querySelectorAll(`[data-live-price-bybit="${symbol}"]`).forEach(el => {
                    const bybitP = price * 1.0008;
                    let dec = bybitP >= 100 ? 2 : (bybitP >= 1 ? 4 : (bybitP >= 0.001 ? 6 : 8));
                    el.textContent = '$' + bybitP.toFixed(dec);
                });

                // 5. Update OKX Comparison Cells Live (-0.06% spread)
                document.querySelectorAll(`[data-live-price-okx="${symbol}"]`).forEach(el => {
                    const okxP = price * 0.9994;
                    let dec = okxP >= 100 ? 2 : (okxP >= 1 ? 4 : (okxP >= 0.001 ? 6 : 8));
                    el.textContent = '$' + okxP.toFixed(dec);
                });

                // 6. Update Heatmap Tile Colors & Content Live
                document.querySelectorAll(`[data-live-tile="${symbol}"]`).forEach(tile => {
                    const chg = changePct;
                    const abs = Math.abs(chg);
                    const intensity = Math.min(1.0, abs / 5.0);
                    let bg, tc;
                    if (chg > 2)        { bg = `rgba(0,212,160,${Math.min(0.85, 0.3+intensity*0.55)})`; tc = '#fff'; }
                    else if (chg > 0.5) { bg = `rgba(0,212,160,${Math.min(0.5, 0.15+intensity*0.35)})`; tc = 'var(--green)'; }
                    else if (chg > -0.5){ bg = 'rgba(136,146,164,0.12)'; tc = 'var(--text-secondary)'; }
                    else if (chg > -2)  { bg = `rgba(255,77,109,${Math.min(0.5, 0.15+intensity*0.35)})`; tc = 'var(--red)'; }
                    else                 { bg = `rgba(255,77,109,${Math.min(0.85, 0.3+intensity*0.55)})`; tc = '#fff'; }

                    tile.style.background = bg;
                    tile.style.color = tc;

                    const chgEl = tile.querySelector('.heatmap-chg');
                    if (chgEl) chgEl.textContent = (chg >= 0 ? '+' : '') + chg.toFixed(2) + '%';
                });

                // 7. Dispatch Global Custom Per-Tick Event for Page-Specific Handlers
                window.dispatchEvent(new CustomEvent('binance-tick', { detail: { symbol, price, changePct, volume } }));
            });
        };
        ws.onerror = (e) => console.warn('Binance Global WS error', e);
        ws.onclose = () => setTimeout(connectBinanceGlobalWS, 2000);
    } catch (e) {
        console.warn('WebSocket unsupported', e);
    }
})();

// Web Audio Chime & Desktop Notification Engine
let soundEnabled = true;
function toggleSoundAlert() {
    soundEnabled = !soundEnabled;
    const btnState = document.getElementById('soundAlertState');
    const btnIcon = document.getElementById('soundAlertIcon');
    if (soundEnabled) {
        btnState.textContent = 'ON';
        btnState.style.color = 'var(--green)';
        btnIcon.textContent = '🔊';
        playSignalChime();
        if ("Notification" in window && Notification.permission !== "granted") {
            Notification.requestPermission();
        }
    } else {
        btnState.textContent = 'OFF';
        btnState.style.color = 'var(--red)';
        btnIcon.textContent = '🔇';
    }
}

function playSignalChime() {
    if (!soundEnabled) return;
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime);
        osc.frequency.setValueAtTime(880, ctx.currentTime + 0.15);
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.6);
    } catch(e) {}
}

function triggerDesktopNotify(title, body) {
    if (soundEnabled) playSignalChime();
    if ("Notification" in window && Notification.permission === "granted") {
        new Notification(title, { body: body, icon: '/favicon.ico' });
    }
}
</script>

@stack('scripts')
</body>
</html>
