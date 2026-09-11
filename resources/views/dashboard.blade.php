@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', '📊 Dashboard')
@section('page-subtitle', 'Real-time crypto market command center')

@section('topbar-actions')
    <form method="POST" action="{{ route('signals.track') }}" style="display:inline">
        @csrf
        <button type="submit" class="btn btn-ghost" title="Track TP/SL outcomes for active signals">
            🎯 Track Outcomes
        </button>
    </form>
@endsection

@section('content')

{{-- ======= STATS GRID ======= --}}
<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-icon">🪙</div>
        <div class="stat-label">Monitored Coins</div>
        <div class="stat-value">{{ $totalCoins }}</div>
        <div class="stat-change">Active USDT pairs</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon">⚡</div>
        <div class="stat-label">Signals Today</div>
        <div class="stat-value">{{ $signalsToday }}</div>
        <div class="stat-change">{{ $signalsSent }} sent &bull; {{ $signalsPending }} pending</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">🟢</div>
        <div class="stat-label">LONG Signals</div>
        <div class="stat-value" style="color:var(--green)">{{ $signalsLong }}</div>
        <div class="stat-change">Today</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon">🔴</div>
        <div class="stat-label">SHORT Signals</div>
        <div class="stat-value" style="color:var(--red)">{{ $signalsShort }}</div>
        <div class="stat-change">Today</div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon">🎯</div>
        <div class="stat-label">Active Tracking</div>
        <div class="stat-value" style="color:var(--purple)">{{ $activeSignals ?? $signalsPending }}</div>
        <div class="stat-change">Pending outcomes</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon">🕐</div>
        <div class="stat-label">Last Scan</div>
        <div class="stat-value" style="font-size:18px">
            {{ $lastScan ? $lastScan->created_at->diffForHumans() : 'Never' }}
        </div>
        <div class="stat-change">
            {{ $lastScan ? $lastScan->created_at->format('H:i:s') : 'Run scanner to start' }}
        </div>
    </div>
</div>

{{-- ======= 🌡️ MARKET HEATMAP ======= --}}
@if($heatmapCoins->isNotEmpty())
<div class="card mb-4" style="background:var(--bg-700);border:1px solid rgba(77,158,255,0.25)">
    <div class="card-header">
        <div class="card-title">🌡️ Market Heatmap — All {{ $heatmapCoins->count() }} Pairs</div>
        <span class="badge badge-blue">Real-time Price Change</span>
    </div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:6px">
            @foreach($heatmapCoins as $h)
            @php
                $chg = $h['change_pct'];
                $abs = abs($chg);
                $intensity = min(1.0, $abs / 5.0);
                if ($chg > 2)       { $bg = 'rgba(0,212,160,'.min(0.85, 0.3+$intensity*0.55).')'; $tc = '#fff'; }
                elseif ($chg > 0.5) { $bg = 'rgba(0,212,160,'.min(0.5, 0.15+$intensity*0.35).')'; $tc = 'var(--green)'; }
                elseif ($chg > -0.5){ $bg = 'rgba(136,146,164,0.12)'; $tc = 'var(--text-secondary)'; }
                elseif ($chg > -2)  { $bg = 'rgba(255,77,109,'.min(0.5, 0.15+$intensity*0.35).')'; $tc = 'var(--red)'; }
                else                { $bg = 'rgba(255,77,109,'.min(0.85, 0.3+$intensity*0.55).')'; $tc = '#fff'; }
            @endphp
            <a href="{{ $h['url'] }}" style="
                display:flex;flex-direction:column;align-items:center;justify-content:center;
                padding:8px 10px;min-width:70px;border-radius:8px;
                background:{{ $bg }};color:{{ $tc }};
                text-decoration:none;border:1px solid rgba(255,255,255,0.05);
                transition:all 0.2s ease;cursor:pointer;
            " class="heatmap-tile" data-live-tile="{{ $h['symbol'] }}">
                <span style="font-size:12px;font-weight:800;letter-spacing:0.3px">{{ $h['base'] }}</span>
                <span style="font-size:10px;font-family:var(--font-mono);margin-top:2px;font-weight:600" class="heatmap-chg" data-live-change="{{ $h['symbol'] }}">
                    {{ $chg >= 0 ? '+' : '' }}{{ number_format($chg, 2) }}%
                </span>
                @if($h['trend'] === 'bull')
                    <span style="font-size:8px;margin-top:2px;color:rgba(255,255,255,0.7)">▲</span>
                @elseif($h['trend'] === 'bear')
                    <span style="font-size:8px;margin-top:2px;color:rgba(255,255,255,0.7)">▼</span>
                @endif
            </a>
            @endforeach
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-top:12px;font-size:11px;color:var(--text-muted)">
            <span style="width:12px;height:12px;border-radius:3px;background:rgba(255,77,109,0.7);display:inline-block"></span> Strong Drop (&lt;-2%)
            <span style="width:12px;height:12px;border-radius:3px;background:rgba(136,146,164,0.2);display:inline-block"></span> Neutral
            <span style="width:12px;height:12px;border-radius:3px;background:rgba(0,212,160,0.7);display:inline-block"></span> Strong Rise (&gt;+2%)
        </div>
    </div>
</div>
@endif

{{-- ======= 🔥 TOP HIGH-POTENTIAL COINS ======= --}}
@if(!empty($topPotentialCoins))
<div class="card mb-4" style="background:linear-gradient(135deg, rgba(255,183,3,0.1) 0%, rgba(23,27,36,0.95) 100%);border:1px solid rgba(255,183,3,0.4);box-shadow:0 8px 32px rgba(255,183,3,0.15)">
    <div class="card-header">
        <div class="card-title">🔥 Top High-Potential Coins (AI & TA Recommendations)</div>
        <span class="badge badge-gold">AI Highest Score</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px">
            @foreach($topPotentialCoins as $pot)
            @php
                $isGradeA = $pot['score'] >= 80;
                $cardStyle = $isGradeA 
                    ? 'border:1px solid rgba(0,212,160,0.6);box-shadow:0 0 20px rgba(0,212,160,0.2);' 
                    : 'border:1px solid var(--border);';
            @endphp
            <div style="background:var(--bg-800);{{ $cardStyle }}border-radius:12px;padding:16px;position:relative;overflow:hidden"
                 data-card-symbol="{{ $pot['coin']->symbol }}"
                 data-entry="{{ $pot['entry'] }}"
                 data-sl="{{ $pot['sl'] }}"
                 data-tp1="{{ $pot['tp1'] }}"
                 data-dir="{{ $pot['direction'] }}">
                @if($isGradeA)
                    <div style="background:var(--green);color:#0a0b0f;font-size:9px;font-weight:800;padding:2px 8px;border-radius:4px;display:inline-block;margin-bottom:6px">
                        🔥 A+ GRADE HIGH PROBABILITY ({{ $pot['score'] }}%)
                    </div>
                @endif
                @if(!empty($pot['is_low_price']))
                    <div style="background:var(--purple);color:#fff;font-size:9px;font-weight:800;padding:2px 8px;border-radius:4px;display:inline-block;margin-bottom:6px;margin-left:4px">
                        ⚡ DYNAMIC SCALP CHOICE (&lt;$10)
                    </div>
                @endif
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <span style="font-size:16px;font-weight:800;color:var(--text-primary);font-family:var(--font-mono)">
                        {{ $pot['coin']->base_asset }} / USDT
                    </span>
                    <span class="badge badge-{{ strtolower($pot['direction']) }}">
                        {{ $pot['direction'] === 'LONG' ? '🟢 LONG' : '🔴 SHORT' }}
                    </span>
                </div>

                <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:8px">
                    <div>
                        <span style="font-size:11px;color:var(--text-muted)">Current Price:</span>
                        <span style="font-size:14px;font-weight:700;font-family:var(--font-mono);color:var(--text-primary)" data-live-price="{{ $pot['coin']->symbol }}">
                            ${{ fmtPrice($pot['last_price']) }}
                        </span>
                    </div>
                    <div style="text-align:right">
                        <span style="font-size:22px;font-weight:800;font-family:var(--font-mono);color:{{ $isGradeA ? 'var(--green)' : 'var(--yellow)' }}">
                            {{ $pot['score'] }}%
                        </span>
                        <span style="font-size:10px;color:var(--text-muted);display:block">Opportunity</span>
                    </div>
                </div>
                <div class="confidence-bar" style="height:4px;margin-bottom:12px">
                    <div class="confidence-fill" style="width:{{ $pot['score'] }}%;background:{{ $isGradeA ? 'var(--grad-green)' : 'var(--grad-gold)' }}"></div>
                </div>

                {{-- Suggested Signal Setup Grid --}}
                @php
                    $cur = (float)$pot['last_price'];
                    $ent = (float)$pot['entry'];
                    $diffPct = $ent > 0 ? (($cur - $ent) / $ent) * 100 : 0;
                    if (abs($diffPct) <= 0.3) {
                        $pLabel = '🟢 IN ENTRY ZONE'; $pBadge = 'badge-pass'; $pAdvice = 'Ideal for Market/Limit Order';
                    } elseif ($diffPct > 0.3 && $diffPct <= 1.5) {
                        $pLabel = '🟡 WAIT FOR DIP'; $pBadge = 'badge-pending'; $pAdvice = 'Use Buy Limit at $' . fmtPrice($ent);
                    } elseif ($diffPct > 1.5) {
                        $pLabel = '🔴 PRICE RUNNING'; $pBadge = 'badge-short'; $pAdvice = 'Do NOT chase — No FOMO';
                    } else {
                        $pLabel = '🔵 DISCOUNT ZONE'; $pBadge = 'badge-blue'; $pAdvice = 'Below entry — Great Limit fill!';
                    }
                @endphp
                <div style="background:var(--bg-900);padding:10px;border-radius:8px;border:1px solid var(--border);margin-bottom:12px">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                        <span style="font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Trading Setup</span>
                        <span class="badge {{ $pBadge }} prox-badge" style="font-size:9px;padding:2px 6px">{{ $pLabel }}</span>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:11px;font-family:var(--font-mono);margin-bottom:6px">
                        <div>🎯 Entry: <strong style="color:var(--blue)">${{ fmtPrice($pot['entry']) }}</strong></div>
                        <div>🛑 SL: <strong style="color:var(--red)">${{ fmtPrice($pot['sl']) }}</strong></div>
                        <div>✅ TP1: <strong style="color:var(--green)">${{ fmtPrice($pot['tp1']) }}</strong></div>
                        <div>🎯 TP2: <strong style="color:var(--green)">${{ fmtPrice($pot['tp2']) }}</strong></div>
                        <div style="grid-column:span 2">🚀 TP3 (+4R): <strong style="color:var(--green)">${{ fmtPrice($pot['tp3']) }}</strong></div>
                    </div>
                    <div style="font-size:10px;color:var(--yellow);font-weight:600">💡 {{ $pAdvice }}</div>
                </div>

                <div style="display:flex;flex-direction:column;gap:3px;margin-bottom:12px">
                    @foreach($pot['drivers'] as $driver)
                        <div style="font-size:10px;color:var(--text-secondary)">⚡ {{ $driver }}</div>
                    @endforeach
                    @if(!empty($pot['macd_bull']))
                        <div style="font-size:10px;color:var(--green)">✅ MACD Bullish Confirmed</div>
                    @endif
                </div>
                <a href="{{ route('coins.show', $pot['coin']->id) }}" class="btn btn-ghost btn-sm" style="width:100%;text-align:center">
                    ⚡ View Analysis & Signals
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ======= PERFORMANCE ANALYTICS ======= --}}
<div class="card mb-4" style="background:linear-gradient(135deg, rgba(23,27,36,0.95) 0%, rgba(30,35,48,0.9) 100%);border:1px solid rgba(102,126,234,0.2)">
    <div class="card-header">
        <div class="card-title">🎯 Forward Testing Performance & Win Rate</div>
        <form method="POST" action="{{ route('signals.track') }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm">🎯 Refresh Tracking</button>
        </form>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;align-items:center">

            {{-- Win Rate --}}
            <div style="text-align:center;padding:16px;background:var(--bg-800);border-radius:12px;border:1px solid var(--border)">
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Win Rate</div>
                <div style="font-size:38px;font-weight:800;font-family:var(--font-mono);color:{{ $winRate >= 60 ? 'var(--green)' : ($winRate >= 40 ? 'var(--yellow)' : 'var(--text-primary)') }};margin:4px 0">
                    {{ $winRate }}%
                </div>
                <div class="confidence-bar" style="height:6px;max-width:140px;margin:0 auto">
                    <div class="confidence-fill" style="width:{{ $winRate }}%;background:{{ $winRate >= 60 ? 'var(--grad-green)' : 'var(--grad-primary)' }}"></div>
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:8px">
                    {{ $totalWins }}W / {{ $totalLosses }}L ({{ $totalClosed }} closed)
                </div>
            </div>

            {{-- Total Net Profit R --}}
            @php
                $netRVal = ($hitTp3Count * 4.0) + ($hitTp2Count * 2.5) + ($hitTp1Count * 1.5) - ($hitSlCount * 1.0);
            @endphp
            <div style="text-align:center;padding:16px;background:var(--bg-800);border-radius:12px;border:1px solid var(--border)">
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Accumulated Net Return</div>
                <div style="font-size:36px;font-weight:800;font-family:var(--font-mono);color:{{ $netRVal >= 0 ? 'var(--green)' : 'var(--red)' }};margin:4px 0">
                    {{ $netRVal >= 0 ? '+' : '' }}{{ number_format($netRVal, 1) }}R
                </div>
                <div style="font-size:11px;color:var(--text-muted)">Net Risk Units (+{{ number_format($netRVal * 10, 1) }}% PnL)</div>
            </div>

            {{-- Outcome Breakdown --}}
            <div style="background:var(--bg-800);padding:16px;border-radius:12px;border:1px solid var(--border)">
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px">Outcome Breakdown</div>
                <div style="display:flex;flex-direction:column;gap:6px">
                    <div style="display:flex;justify-content:space-between;font-size:12px">
                        <span style="color:var(--green)">🚀 Hit TP3 (+4.0R)</span><span class="mono font-semibold">{{ $hitTp3Count }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12px">
                        <span style="color:var(--green)">🎯 Hit TP2 (+2.5R)</span><span class="mono font-semibold">{{ $hitTp2Count }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12px">
                        <span style="color:var(--green)">✅ Hit TP1 (+1.5R)</span><span class="mono font-semibold">{{ $hitTp1Count }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12px">
                        <span style="color:var(--red)">🛑 Hit SL (-1.0R)</span><span class="mono font-semibold">{{ $hitSlCount }}</span>
                    </div>
                </div>
            </div>

            {{-- Weekly Signal Bar Chart --}}
            <div style="background:var(--bg-800);padding:16px;border-radius:12px;border:1px solid var(--border);display:flex;flex-direction:column;justify-content:space-between">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                    <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">SIGNALS — LAST 7 DAYS</div>
                    <div style="font-size:9px;display:flex;gap:6px;font-weight:700">
                        <span style="color:var(--green)">🟢 LONG</span>
                        <span style="color:var(--red)">🔴 SHORT</span>
                    </div>
                </div>
                @php $maxTotal = $weeklySignals->max('total') ?: 1; @endphp
                <div style="display:flex;align-items:flex-end;gap:6px;height:70px;padding-top:6px">
                    @foreach($weeklySignals as $day)
                    @php 
                        $tot   = $day['total'];
                        $long  = $day['long'];
                        $short = $day['short'];
                        $totalH = $maxTotal > 0 ? min(48, max(8, round(($tot / $maxTotal) * 48))) : 0;
                        $longH  = $tot > 0 ? round(($long / $tot) * $totalH) : 0;
                        $shortH = $tot > 0 ? ($totalH - $longH) : 0;
                    @endphp
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:3px" title="{{ $day['label'] }}: {{ $long }} LONG / {{ $short }} SHORT">
                        <div style="font-size:9px;color:var(--text-muted);font-weight:700;margin-bottom:1px">{{ $tot > 0 ? $tot : '' }}</div>
                        <div style="width:100%;max-width:16px;display:flex;flex-direction:column;border-radius:3px;overflow:hidden">
                            @if($shortH > 0)
                                <div style="height:{{ max(3, $shortH) }}px;background:var(--red);opacity:0.85"></div>
                            @endif
                            @if($longH > 0)
                                <div style="height:{{ max(3, $longH) }}px;background:var(--green)"></div>
                            @endif
                            @if($tot == 0)
                                <div style="height:3px;background:var(--border);border-radius:2px"></div>
                            @endif
                        </div>
                        <span style="font-size:10px;color:var(--text-secondary);font-weight:600;margin-top:2px">{{ $day['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ======= MACRO ECONOMIC CALENDAR ======= --}}
<div class="card mb-4" style="background:var(--bg-700);border:1px solid rgba(255,77,109,0.3)">
    <div class="card-header">
        <div class="card-title">📅 Macro Economic Calendar & Volatility Alerts</div>
        <span class="badge badge-short">⚠️ High Impact News Radar</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
            <div style="background:var(--bg-800);border:1px solid var(--border);border-radius:10px;padding:12px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <span class="badge badge-short" style="font-size:10px">🔥 HIGH IMPACT</span>
                    <span style="font-size:11px;color:var(--text-muted)">Today, 19:30 WIB</span>
                </div>
                <div style="font-weight:700;font-size:13px;color:var(--text-primary)">🇺🇸 US CPI Inflation Rate (YoY)</div>
                <div style="font-size:11px;color:var(--text-secondary);margin-top:4px">Forecast: 2.9% | Previous: 3.0%</div>
                <div style="font-size:10px;color:var(--yellow);margin-top:6px;font-weight:600">⚠️ Expect high volatility on BTC/ETH</div>
            </div>
            <div style="background:var(--bg-800);border:1px solid var(--border);border-radius:10px;padding:12px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <span class="badge badge-purple" style="font-size:10px">⭐ FED DECISION</span>
                    <span style="font-size:11px;color:var(--text-muted)">Tomorrow, 01:00 WIB</span>
                </div>
                <div style="font-weight:700;font-size:13px;color:var(--text-primary)">🏛️ FOMC Rate Decision & Press Conf</div>
                <div style="font-size:11px;color:var(--text-secondary);margin-top:4px">Forecast: 5.25% | Previous: 5.50%</div>
                <div style="font-size:10px;color:var(--green);margin-top:6px;font-weight:600">🚀 Potential Rate Cut Catalyst</div>
            </div>
            <div style="background:var(--bg-800);border:1px solid var(--border);border-radius:10px;padding:12px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <span class="badge badge-blue" style="font-size:10px">📊 JOBS REPORT</span>
                    <span style="font-size:11px;color:var(--text-muted)">Friday, 19:30 WIB</span>
                </div>
                <div style="font-weight:700;font-size:13px;color:var(--text-primary)">💼 US Non-Farm Payrolls (NFP)</div>
                <div style="font-size:11px;color:var(--text-secondary);margin-top:4px">Forecast: 175K | Previous: 206K</div>
                <div style="font-size:10px;color:var(--text-muted);margin-top:6px">Dollar Index Impact</div>
            </div>
        </div>
    </div>
</div>

{{-- ======= AI MARKET SENTIMENT ======= --}}
<div class="card mb-4" style="background:var(--bg-700);border:1px solid rgba(168,85,247,0.3)">
    <div class="card-header">
        <div class="card-title">🤖 AI Market Sentiment & News Insights</div>
        <span class="badge badge-purple">Gemini AI Powered</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start">
            @php $sentScore = $marketSentiment['score'] ?? $marketSentiment['bullish_pct'] ?? 50; @endphp
            <div style="background:var(--bg-800);padding:20px;border-radius:12px;border:1px solid var(--border);text-align:center">
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Market Sentiment Index</div>
                <div style="font-size:42px;font-weight:800;font-family:var(--font-mono);color:{{ $sentScore >= 55 ? 'var(--green)' : 'var(--yellow)' }};margin:8px 0">
                    {{ $sentScore }}%
                </div>
                <div style="font-size:13px;font-weight:700;color:var(--text-primary)">{{ $marketSentiment['label'] ?? 'Neutral' }}</div>
                <div class="confidence-bar" style="height:6px;margin-top:10px">
                    <div class="confidence-fill" style="width:{{ $sentScore }}%;background:var(--grad-gold)"></div>
                </div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px">Latest AI Crypto News Insights</div>
                @if($recentNews->isEmpty())
                    <div style="color:var(--text-muted);font-size:12px">Run <code>php artisan scanner:fetch-news</code> to analyze crypto news</div>
                @else
                    <div style="display:flex;flex-direction:column;gap:10px">
                        @foreach($recentNews as $news)
                        <div style="background:var(--bg-800);border:1px solid var(--border);border-radius:8px;padding:12px">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
                                <span style="font-size:12px;font-weight:700;color:var(--text-primary)">{{ $news->title }}</span>
                                <span class="badge badge-{{ $news->sentiment_badge_color }}" style="font-size:10px">{{ $news->sentiment_emoji }}</span>
                            </div>
                            <div style="font-size:11px;color:var(--purple);margin-bottom:4px">{{ $news->ai_summary }}</div>
                            <div style="font-size:10px;color:var(--text-muted)">
                                Source: {{ $news->source }} &bull; Risk: <span style="color:var(--{{ $news->risk_badge_color }})">{{ strtoupper($news->risk_level) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ======= TWO COLUMN: Recent Signals + Logs ======= --}}
<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div class="card-title">⚡ Recent Signals</div>
            <a href="{{ route('signals.index') }}" class="btn btn-ghost btn-sm">View All</a>
        </div>
        @if($recentSignals->isEmpty())
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>No signals yet</h3>
                <p>Click "Run Scanner" to generate your first signals</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Coin</th>
                        <th>Direction</th>
                        <th>Entry</th>
                        <th>Outcome</th>
                        <th>Conf.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentSignals as $signal)
                    <tr>
                        <td class="text-primary">
                            <a href="{{ route('signals.show', $signal) }}" style="color:var(--blue);font-weight:700">
                                {{ $signal->coin->base_asset ?? 'N/A' }}
                            </a>
                            @if($signal->tier === 'VIP')
                                <span class="badge badge-purple" style="font-size:9px;padding:1px 4px">VIP</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ strtolower($signal->direction) }}">
                                {{ $signal->direction }}
                            </span>
                        </td>
                        <td class="price mono">${{ fmtPrice($signal->entry_price) }}</td>
                        <td>
                            <span class="badge badge-{{ $signal->outcome_badge_color }}">
                                {{ $signal->outcome_label }}
                            </span>
                        </td>
                        <td class="mono" style="font-size:12px;color:{{ $signal->confidence_score >= 70 ? 'var(--green)' : 'var(--text-secondary)' }}">{{ $signal->confidence_score }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">📋 Recent Activity</div>
            <a href="{{ route('logs.index') }}" class="btn btn-ghost btn-sm">View All</a>
        </div>
        @if($recentLogs->isEmpty())
            <div class="empty-state">
                <div class="icon">📜</div>
                <h3>No logs yet</h3>
                <p>Scanner activity will appear here</p>
            </div>
        @else
            <div class="log-terminal" style="border:none;border-radius:0">
                @foreach($recentLogs as $log)
                <div class="log-line {{ $log->status }}">
                    <span class="log-time">{{ $log->created_at->format('H:i:s') }}</span>
                    <span class="log-stage">{{ $log->stage }}</span>
                    @if($log->coin)
                        <span class="log-coin">{{ $log->coin->symbol }}</span>
                    @else
                        <span class="log-coin" style="color:var(--text-muted)">SYSTEM</span>
                    @endif
                    <span class="log-msg">{{ Str::limit($log->message, 60) }}</span>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
window.addEventListener('binance-tick', (e) => {
    const { symbol, price } = e.detail;
    // Per-tick dynamic setup update for potential cards
    const card = document.querySelector(`[data-card-symbol="${symbol}"]`);
    if (!card) return;
    
    const entry = parseFloat(card.getAttribute('data-entry') || price);
    const sl = parseFloat(card.getAttribute('data-sl') || price * 0.97);
    const tp1 = parseFloat(card.getAttribute('data-tp1') || price * 1.03);
    const dir = card.getAttribute('data-dir');

    // Update proximity advice badge live
    const proxBadge = card.querySelector('.prox-badge');
    if (proxBadge) {
        const diffPct = entry > 0 ? ((price - entry) / entry) * 100 : 0;
        if (Math.abs(diffPct) <= 0.3) {
            proxBadge.className = 'badge badge-pass prox-badge';
            proxBadge.textContent = '🟢 IN ENTRY ZONE';
        } else if (diffPct > 0.3 && diffPct <= 1.5) {
            proxBadge.className = 'badge badge-pending prox-badge';
            proxBadge.textContent = '🟡 WAIT FOR DIP';
        } else if (diffPct > 1.5) {
            proxBadge.className = 'badge badge-short prox-badge';
            proxBadge.textContent = '🔴 PRICE RUNNING';
        } else {
            proxBadge.className = 'badge badge-blue prox-badge';
            proxBadge.textContent = '🔵 DISCOUNT ZONE';
        }
    }
});
</script>
@endpush

@endsection
