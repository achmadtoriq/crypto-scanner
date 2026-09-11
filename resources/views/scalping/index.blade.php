@extends('layouts.app')

@section('title', '15M Scalper Radar')
@section('page-title', '⚡ 15M Scalping Radar')
@section('page-subtitle', 'Fast-paced 15-minute crypto scalping setups with live ATR risk bounds & per-tick updates')

@section('topbar-actions')
    <button class="btn btn-scanner" onclick="run15mScalpScan()" id="btnRun15mScalp">
        <span id="btnIconScalp">⚡</span>
        <span id="btnTextScalp">Run 15M Scalp Scan</span>
    </button>
@endsection

@section('content')

{{-- ======= SCALPING EXECUTION TIMING BANNER ======= --}}
<div class="card mb-4" style="background:linear-gradient(135deg, rgba(114,9,183,0.2) 0%, rgba(0,212,160,0.15) 100%);border:1px solid rgba(0,212,160,0.4);border-radius:14px;padding:18px 24px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
        <div style="display:flex;align-items:center;gap:14px">
            <div style="font-size:32px">⏱️</div>
            <div>
                <div style="display:flex;align-items:center;gap:8px">
                    <h3 style="margin:0;font-size:16px;font-weight:800;color:#fff">15M Scalping Execution Clock & Session Radar</h3>
                    <span class="badge badge-pass" id="sessionStatusBadge" style="font-size:10px">🔥 PEAK VOLATILITY WINDOW</span>
                </div>
                <div style="font-size:12px;color:var(--text-secondary);margin-top:4px" id="timingAdviceText">
                    Waktu terbaik scalping: Masuk posisi pada 1-3 menit pertama awal candle 15m atau saat terjadi breakout candle close!
                </div>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:16px;background:var(--bg-800);padding:10px 18px;border-radius:10px;border:1px solid var(--border)">
            <div style="text-align:center">
                <div style="font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase">Next 15M Candle Close</div>
                <div class="mono" id="candleCountdownTimer" style="font-size:18px;font-weight:800;color:var(--green)">00m 00s</div>
            </div>
            <div style="height:28px;width:1px;background:var(--border)"></div>
            <div style="text-align:center">
                <div style="font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase">Active Market Session</div>
                <div style="font-size:13px;font-weight:800;color:var(--purple)" id="activeSessionName">US / NY Session</div>
            </div>
        </div>
    </div>
</div>

{{-- ======= SCALP STATS GRID ======= --}}
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card purple">
        <div class="stat-icon">⚡</div>
        <div class="stat-label">Total 15M Candidates</div>
        <div class="stat-value" style="color:var(--purple)">{{ $totalCandidates }}</div>
        <div class="stat-change">Active momentum setups</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">🟢</div>
        <div class="stat-label">LONG Scalp Signals</div>
        <div class="stat-value" style="color:var(--green)">{{ $longCount }}</div>
        <div class="stat-change">Bullish Momentum</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon">🔴</div>
        <div class="stat-label">SHORT Scalp Signals</div>
        <div class="stat-value" style="color:var(--red)">{{ $shortCount }}</div>
        <div class="stat-change">Bearish Momentum</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon">💰</div>
        <div class="stat-label">Under $10 Micro Scalps</div>
        <div class="stat-value" style="color:var(--yellow)">{{ $lowPriceCount }}</div>
        <div class="stat-change">Ideal for retail capital</div>
    </div>
</div>

{{-- ======= FILTER TABS & SEARCH ======= --}}
<div class="card mb-4" style="background:var(--bg-700);padding:14px 20px">
    <div style="display:flex;align-items:center;justify-space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:8px">
            <button class="btn btn-sm btn-ghost active-tab" onclick="filterScalpCategory('all', this)" id="tab-all">
                🔥 All 15M Scalps ({{ $totalCandidates }})
            </button>
            <button class="btn btn-sm btn-ghost" onclick="filterScalpCategory('long', this)" id="tab-long">
                🟢 LONG Only ({{ $longCount }})
            </button>
            <button class="btn btn-sm btn-ghost" onclick="filterScalpCategory('short', this)" id="tab-short">
                🔴 SHORT Only ({{ $shortCount }})
            </button>
            <button class="btn btn-sm btn-ghost" onclick="filterScalpCategory('lowprice', this)" id="tab-lowprice" style="border:1px solid rgba(255,183,3,0.3)">
                💰 Under $10 Coins ({{ $lowPriceCount }})
            </button>
        </div>
        <div style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:8px">
            <span class="badge badge-pass" style="font-size:10px;padding:4px 8px">🔥 HIGH PROBABILITY ONLY (75%+)</span>
            <span>⚡ Live per-tick updates from Binance WS</span>
        </div>
    </div>
</div>

{{-- ======= SCALPING CANDIDATES GRID ======= --}}
@if(empty($scalpCandidates))
    <div class="card" style="padding:40px;text-align:center">
        <div style="font-size:36px;margin-bottom:12px">⚡</div>
        <h3>No 15M Scalp Candidates Found</h3>
        <p style="color:var(--text-muted)">Run the 15M scanner to fetch fresh micro-momentum setups.</p>
        <button class="btn btn-primary" onclick="run15mScalpScan()" style="margin-top:16px">Run 15M Scalp Scan</button>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px" id="scalpGridContainer">
        @foreach($scalpCandidates as $s)
        @php
            $isLong = $s['direction'] === 'LONG';
            $isGradeA = $s['score'] >= 75;
            $cardBorder = $isGradeA 
                ? ($isLong ? 'border:1px solid rgba(0,212,160,0.6);box-shadow:0 0 20px rgba(0,212,160,0.15);' : 'border:1px solid rgba(255,77,109,0.6);box-shadow:0 0 20px rgba(255,77,109,0.15);') 
                : 'border:1px solid var(--border);';
            $curP = (float)$s['last_price'];
            $entP = (float)$s['entry'];
            $diffPct = $entP > 0 ? (($curP - $entP) / $entP) * 100 : 0;
            if (abs($diffPct) <= 0.3) {
                $pBadge = 'badge-pass'; $pLabel = '🟢 IN ENTRY ZONE'; $pAdvice = 'Ideal for Market/Limit Order';
            } elseif ($diffPct > 0.3 && $diffPct <= 1.5) {
                $pBadge = 'badge-pending'; $pLabel = '🟡 WAIT FOR DIP'; $pAdvice = 'Use Buy Limit at $' . fmtPrice($entP);
            } elseif ($diffPct > 1.5) {
                $pBadge = 'badge-short'; $pLabel = '🔴 PRICE RUNNING'; $pAdvice = 'Do NOT chase — No FOMO';
            } else {
                $pBadge = 'badge-blue'; $pLabel = '🔵 DISCOUNT ZONE'; $pAdvice = 'Below entry — Great Limit fill!';
            }
        @endphp
        <div class="card scalp-card-item" 
             style="background:var(--bg-700);{{ $cardBorder }}border-radius:14px;padding:20px;position:relative"
             data-dir="{{ strtolower($s['direction']) }}"
             data-lowprice="{{ $s['is_low_price'] ? 'true' : 'false' }}"
             data-scalp-symbol="{{ $s['coin']->symbol }}"
             data-entry="{{ $s['entry'] }}"
             data-sl="{{ $s['sl'] }}"
             data-tp1="{{ $s['tp1'] }}"
             data-tp3="{{ $s['tp3'] }}">

            {{-- Badges --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-size:18px;font-weight:800;color:var(--text-primary);font-family:var(--font-mono)">
                        {{ $s['coin']->base_asset }} / USDT
                    </span>
                    <span class="badge badge-{{ strtolower($s['direction']) }}">
                        {{ $isLong ? '🟢 LONG' : '🔴 SHORT' }}
                    </span>
                </div>
                <span class="badge badge-purple" style="font-size:10px">15M SCALP</span>
            </div>

            @if($s['is_low_price'])
                <div style="background:rgba(255,183,3,0.15);color:var(--yellow);border:1px solid rgba(255,183,3,0.3);font-size:9px;font-weight:800;padding:3px 8px;border-radius:4px;display:inline-block;margin-bottom:10px">
                    💰 LOW-PRICE SCALP (&lt;$10)
                </div>
            @endif

            {{-- Price & Score --}}
            <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:12px">
                <div>
                    <span style="font-size:11px;color:var(--text-muted)">Live Price:</span>
                    <span class="mono" style="font-size:18px;font-weight:800;color:var(--text-primary);display:block" data-live-price="{{ $s['coin']->symbol }}">
                        ${{ fmtPrice($s['last_price']) }}
                    </span>
                </div>
                <div style="text-align:right">
                    <span class="mono" style="font-size:24px;font-weight:800;color:{{ $isLong ? 'var(--green)' : 'var(--red)' }}">
                        {{ $s['score'] }}%
                    </span>
                    <span style="font-size:10px;color:var(--text-muted);display:block">Momentum Score</span>
                </div>
            </div>

            {{-- Drivers List --}}
            <div style="background:var(--bg-800);padding:10px 12px;border-radius:8px;margin-bottom:14px;border:1px solid var(--border)">
                <div style="font-size:10px;color:var(--text-muted);font-weight:700;margin-bottom:4px;text-transform:uppercase">Key Scalp Drivers</div>
                @foreach($s['drivers'] as $drv)
                    <div style="font-size:11px;color:var(--text-secondary);margin-top:2px">⚡ {{ $drv }}</div>
                @endforeach
            </div>

            {{-- Scalp Trade Setup Table --}}
            <div style="background:var(--bg-800);border-radius:10px;padding:12px;margin-bottom:14px;border:1px solid var(--border)">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                    <span style="font-size:11px;font-weight:700;color:var(--text-muted)">SCALPING SETUP (15M)</span>
                    <span class="badge {{ $pBadge }} prox-badge-scalp" style="font-size:10px">{{ $pLabel }}</span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-family:var(--font-mono);font-size:12px">
                    <div>🎯 Entry: <strong style="color:var(--blue)">${{ fmtPrice($s['entry']) }}</strong></div>
                    <div>🛑 SL: <strong style="color:var(--red)">${{ fmtPrice($s['sl']) }}</strong></div>
                    <div>✅ TP1 (Quick): <strong style="color:var(--green)">${{ fmtPrice($s['tp1']) }}</strong></div>
                    <div>🚀 TP2: <strong style="color:var(--green)">${{ fmtPrice($s['tp2']) }}</strong></div>
                </div>
            </div>

            {{-- Visual Price Ruler 📏 --}}
            @php
                $minP = min((float)$s['sl'], (float)$s['tp3'], $curP) * 0.998;
                $maxP = max((float)$s['sl'], (float)$s['tp3'], $curP) * 1.002;
                $range = $maxP - $minP;
                $posPct = $range > 0 ? min(98, max(2, (($curP - $minP) / $range) * 100)) : 50;
            @endphp
            <div style="margin-bottom:16px">
                <div style="display:flex;justify-content:space-between;font-size:9px;color:var(--text-muted);font-family:var(--font-mono);margin-bottom:4px">
                    <span>SL: ${{ fmtPrice($s['sl']) }}</span>
                    <span>Entry: ${{ fmtPrice($s['entry']) }}</span>
                    <span>TP1: ${{ fmtPrice($s['tp1']) }}</span>
                </div>
                <div style="position:relative;height:8px;background:var(--bg-900);border-radius:4px;overflow:hidden;border:1px solid var(--border)">
                    <div class="ruler-progress-bar" style="position:absolute;top:0;left:0;height:100%;width:100%;background:linear-gradient(90deg, var(--red) 0%, var(--yellow) 40%, var(--green) 100%);opacity:0.3"></div>
                    <div class="ruler-now-pin" style="position:absolute;top:-2px;left:{{ $posPct }}%;width:4px;height:12px;background:#fff;border-radius:2px;box-shadow:0 0 8px #fff;transition:left 0.2s ease"></div>
                </div>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:8px">
                <button class="btn btn-primary btn-sm" style="flex:1" 
                        onclick="copyScalpSetup('{{ $s['coin']->base_asset }}', '{{ fmtPrice($s['entry']) }}', '{{ fmtPrice($s['sl']) }}', '{{ fmtPrice($s['tp1']) }}', '{{ fmtPrice($s['tp2']) }}', '{{ $s['direction'] }}')">
                    📋 Copy Scalp Setup
                </button>
                <a href="{{ route('coins.show', [$s['coin'], 'interval' => '15m']) }}" class="btn btn-ghost btn-sm">
                    📈 View 15M Chart
                </a>
            </div>
        </div>
        @endforeach
    </div>
@endif

@endsection

@push('scripts')
<script>
// Filter Tab Switcher
function filterScalpCategory(cat, btn) {
    document.querySelectorAll('.active-tab').forEach(el => el.classList.remove('active-tab'));
    btn.classList.add('active-tab');

    const items = document.querySelectorAll('.scalp-card-item');
    items.forEach(item => {
        const dir = item.getAttribute('data-dir');
        const isLow = item.getAttribute('data-lowprice') === 'true';

        if (cat === 'all') {
            item.style.display = 'block';
        } else if (cat === 'long' && dir === 'long') {
            item.style.display = 'block';
        } else if (cat === 'short' && dir === 'short') {
            item.style.display = 'block';
        } else if (cat === 'lowprice' && isLow) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Copy Scalp Setup to Clipboard
function copyScalpSetup(asset, entry, sl, tp1, tp2, dir) {
    const text = `⚡ 15M SCALPING SETUP: ${asset}/USDT (${dir})\n` +
                 `🎯 Entry: $${entry}\n` +
                 `🛑 Stop Loss: $${sl}\n` +
                 `✅ Take Profit 1: $${tp1}\n` +
                 `🚀 Take Profit 2: $${tp2}\n` +
                 ` Risk Management: 1-2% Account Risk. Trail SL to BEP after TP1 hit!`;

    navigator.clipboard.writeText(text).then(() => {
        if (typeof showFlash === 'function') {
            showFlash(`📋 Copied 15M ${asset} Scalp Trade Setup to clipboard!`, 'success');
        } else {
            alert(`Copied 15M ${asset} Scalp Trade Setup!`);
        }
    }).catch(err => alert('Copy failed: ' + err));
}

// Run 15M Scalp Scan via AJAX
function run15mScalpScan() {
    const btn  = document.getElementById('btnRun15mScalp');
    const icon = document.getElementById('btnIconScalp');
    const text = document.getElementById('btnTextScalp');

    btn.disabled = true;
    icon.innerHTML = '<span class="spinner"></span>';
    text.textContent = 'Scanning 15M...';

    fetch('{{ route("scanner.run") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ interval: '15m' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (typeof showFlash === 'function') {
                showFlash(`⚡ 15M Scalp Scan Complete! ${data.stats.signals_generated} new scalp signals generated.`, 'success');
            }
            setTimeout(() => location.reload(), 1500);
        } else {
            alert('Scan error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => alert('Network error: ' + err.message))
    .finally(() => {
        btn.disabled = false;
        icon.textContent = '⚡';
        text.textContent = 'Run 15M Scalp Scan';
    });
}

// Real-time per-tick listener for dynamic 15M ruler & entry status updates
window.addEventListener('binance-tick', (e) => {
    const { symbol, price } = e.detail;
    const cards = document.querySelectorAll(`[data-scalp-symbol="${symbol}"]`);
    cards.forEach(card => {
        const entry = parseFloat(card.getAttribute('data-entry') || price);
        const sl    = parseFloat(card.getAttribute('data-sl') || price * 0.99);
        const tp3   = parseFloat(card.getAttribute('data-tp3') || price * 1.03);

        // Update Proximity Badge
        const proxBadge = card.querySelector('.prox-badge-scalp');
        if (proxBadge) {
            const diffPct = entry > 0 ? ((price - entry) / entry) * 100 : 0;
            if (Math.abs(diffPct) <= 0.3) {
                proxBadge.className = 'badge badge-pass prox-badge-scalp';
                proxBadge.textContent = '🟢 IN ENTRY ZONE';
            } else if (diffPct > 0.3 && diffPct <= 1.5) {
                proxBadge.className = 'badge badge-pending prox-badge-scalp';
                proxBadge.textContent = '🟡 WAIT FOR DIP';
            } else if (diffPct > 1.5) {
                proxBadge.className = 'badge badge-short prox-badge-scalp';
                proxBadge.textContent = '🔴 PRICE RUNNING';
            } else {
                proxBadge.className = 'badge badge-blue prox-badge-scalp';
                proxBadge.textContent = '🔵 DISCOUNT ZONE';
            }
        }

        // Update Ruler Pin Position
        const minP = Math.min(sl, tp3, price) * 0.998;
        const maxP = Math.max(sl, tp3, price) * 1.002;
        const range = maxP - minP;
        const posPct = range > 0 ? Math.min(98, Math.max(2, ((price - minP) / range) * 100)) : 50;

        const pin = card.querySelector('.ruler-now-pin');
        if (pin) {
            pin.style.left = posPct + '%';
        }
    });
});

// Live 15M Candle Close Countdown & Session Radar Clock
function updateScalpingClock() {
    const now = new Date();
    const minutes = now.getMinutes();
    const seconds = now.getSeconds();

    const remainingMinutes = 14 - (minutes % 15);
    const remainingSeconds = 59 - seconds;

    const mStr = String(remainingMinutes).padStart(2, '0');
    const sStr = String(remainingSeconds).padStart(2, '0');

    const timerEl = document.getElementById('candleCountdownTimer');
    if (timerEl) {
        timerEl.textContent = `${mStr}m ${sStr}s`;
        if (remainingMinutes === 0 && remainingSeconds <= 30) {
            timerEl.style.color = 'var(--yellow)';
        } else {
            timerEl.style.color = 'var(--green)';
        }
    }

    // Determine Market Session (WIB / UTC+7)
    const hour = now.getHours();
    const sessionNameEl  = document.getElementById('activeSessionName');
    const sessionBadgeEl = document.getElementById('sessionStatusBadge');
    const timingAdviceEl = document.getElementById('timingAdviceText');

    if (hour >= 19 || hour < 2) {
        if (sessionNameEl) sessionNameEl.textContent = '🌎 US / New York Session';
        if (sessionBadgeEl) {
            sessionBadgeEl.className = 'badge badge-pass';
            sessionBadgeEl.textContent = '🔥 PEAK VOLATILITY (BEST TIME TO SCALP)';
        }
        if (timingAdviceEl) timingAdviceEl.textContent = 'Sesi US sedang berlangsung (Volume & Pergerakan Harga Maksimal). Waktu terbaik untuk Scalping 15M!';
    } else if (hour >= 14 && hour < 19) {
        if (sessionNameEl) sessionNameEl.textContent = '🌍 European / London Session';
        if (sessionBadgeEl) {
            sessionBadgeEl.className = 'badge badge-blue';
            sessionBadgeEl.textContent = '⚡ HIGH VOLUME SCALPING';
        }
        if (timingAdviceEl) timingAdviceEl.textContent = 'Sesi London aktif (Volume Tinggi). Bagus untuk Scalping Breakout!';
    } else if (hour >= 7 && hour < 14) {
        if (sessionNameEl) sessionNameEl.textContent = '🌏 Asian Session';
        if (sessionBadgeEl) {
            sessionBadgeEl.className = 'badge badge-pending';
            sessionBadgeEl.textContent = '🟡 MODERATE VOLATILITY';
        }
        if (timingAdviceEl) timingAdviceEl.textContent = 'Sesi Asia (Volatilitas Sedang). Cocok untuk Scalping di dekat Level Support & Resistance!';
    } else {
        if (sessionNameEl) sessionNameEl.textContent = '💤 Low Volume Phase';
        if (sessionBadgeEl) {
            sessionBadgeEl.className = 'badge badge-short';
            sessionBadgeEl.textContent = '💤 LOW VOLATILITY (CAUTION)';
        }
        if (timingAdviceEl) timingAdviceEl.textContent = 'Pasar cenderung Sideways (Volume Rendah). Disarankan berhati-hati atau menunggu Sesi Asia/London.';
    }
}

setInterval(updateScalpingClock, 1000);
updateScalpingClock();
</script>
@endpush
