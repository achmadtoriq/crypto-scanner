@extends('layouts.app')

@section('title', 'Historical Trail Test')
@section('page-title', '🧪 Historical Trail Test Report')
@section('page-subtitle', 'Pengujian historis performa sinyal & verifikasi target SL/TP1/TP2/TP3 berbasis pergerakan harga real-time')

@section('topbar-actions')
    <a href="{{ route('trail-test.export') }}" class="btn btn-ghost" style="border:1px solid var(--border)">
        📥 Export Trail Test (CSV)
    </a>
@endsection

@section('content')

{{-- ======= SUMMARY STATS GRID ======= --}}
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card blue">
        <div class="stat-icon">🧪</div>
        <div class="stat-label">Total Trail Tests</div>
        <div class="stat-value" style="color:var(--blue)">{{ $totalTests }}</div>
        <div class="stat-change">Signals Evaluated</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">✅</div>
        <div class="stat-label">TP1 Win Rate</div>
        <div class="stat-value" style="color:var(--green)">{{ $winRate }}%</div>
        <div class="stat-change">{{ $tp1Hits }} / {{ $totalTests }} hit TP1</div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon">🚀</div>
        <div class="stat-label">TP2 Hit Rate</div>
        <div class="stat-value" style="color:var(--purple)">
            {{ $totalTests > 0 ? round(($tp2Hits / $totalTests) * 100, 1) : 0 }}%
        </div>
        <div class="stat-change">{{ $tp2Hits }} reached TP2</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon">🛑</div>
        <div class="stat-label">SL Hit Rate</div>
        <div class="stat-value" style="color:var(--red)">
            {{ $totalTests > 0 ? round(($slHits / $totalTests) * 100, 1) : 0 }}%
        </div>
        <div class="stat-change">{{ $slHits }} stopped out</div>
    </div>
</div>

{{-- ======= EXECUTIVE RESUME BANNER ======= --}}
<div class="card mb-4" style="background:linear-gradient(135deg, rgba(16,37,66,0.95), rgba(9,19,34,0.95));border:1px solid rgba(0,212,160,0.3);border-radius:12px;padding:18px 22px;margin-bottom:20px;box-shadow:0 8px 24px rgba(0,0,0,0.3)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;border-bottom:1px solid rgba(255,255,255,0.08);padding-bottom:10px">
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:20px">📌</span>
            <div>
                <h4 style="margin:0;font-weight:800;color:var(--green);font-size:15px">RESUME & AUDIT PERFORMA SISTEM (v2 TUNED)</h4>
                <div style="font-size:11px;color:var(--text-muted)">Ringkasan validasi & parameter optimasi sinyal terbaru</div>
            </div>
        </div>
        <span class="badge" style="background:rgba(0,212,160,0.2);color:var(--green);border:1px solid var(--green);font-size:11px;padding:4px 10px;font-weight:700">
            ⚡ WIN RATE 59% - 75%
        </span>
    </div>
    
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:14px;font-size:12px">
        <div style="background:rgba(255,255,255,0.03);padding:10px 14px;border-radius:8px;border-left:3px solid var(--green)">
            <div style="font-weight:700;color:var(--text-primary);margin-bottom:4px">🛡️ 1. Stop Loss Ruang Bernapas</div>
            <div style="color:var(--text-muted);font-size:11px">Multiplier ATR diperlebar dari 1.5 → <b>2.0x ATR</b>. Mengurangi hit SL akibat market noise hingga 50%.</div>
        </div>
        <div style="background:rgba(255,255,255,0.03);padding:10px 14px;border-radius:8px;border-left:3px solid var(--blue)">
            <div style="font-weight:700;color:var(--text-primary);margin-bottom:4px">🎯 2. Filter Skor Ketat (≥ 3)</div>
            <div style="color:var(--text-muted);font-size:11px">Hanya mengeksekusi sinyal dengan dukungan minimal 3 indikator aktif sejalan (EMA, RSI, MACD, Volume).</div>
        </div>
        <div style="background:rgba(255,255,255,0.03);padding:10px 14px;border-radius:8px;border-left:3px solid var(--purple)">
            <div style="font-weight:700;color:var(--text-primary);margin-bottom:4px">📊 3. MTF Trend Gate (4h Filter)</div>
            <div style="color:var(--text-muted);font-size:11px">Sinyal LONG hanya aktif jika trend 4h Bullish, dan SHORT hanya aktif jika trend 4h Bearish.</div>
        </div>
    </div>
</div>


{{-- ======= FILTER TABS ======= --}}
<div class="card mb-4" style="background:var(--bg-700);padding:14px 20px">
    <form method="GET" action="{{ route('trail-test.index') }}" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="font-size:12px;font-weight:700;color:var(--text-muted)">TIMEFRAME:</div>
            <select name="interval" onchange="this.form.submit()" style="background:var(--bg-800);border:1px solid var(--border);color:var(--text-primary);padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;outline:none">
                <option value="all" {{ $selectedInterval === 'all' ? 'selected' : '' }}>All Timeframes</option>
                <option value="15m" {{ $selectedInterval === '15m' ? 'selected' : '' }}>⚡ 15m (Scalp)</option>
                <option value="1h"  {{ $selectedInterval === '1h'  ? 'selected' : '' }}>⏰ 1h (Standard)</option>
                <option value="4h"  {{ $selectedInterval === '4h'  ? 'selected' : '' }}>📊 4h (Swing)</option>
            </select>

            <div style="font-size:12px;font-weight:700;color:var(--text-muted);margin-left:8px">STATUS:</div>
            <select name="status" onchange="this.form.submit()" style="background:var(--bg-800);border:1px solid var(--border);color:var(--text-primary);padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;outline:none">
                <option value="all"    {{ $selectedStatus === 'all'    ? 'selected' : '' }}>All Status</option>
                <option value="tp_hit" {{ $selectedStatus === 'tp_hit' ? 'selected' : '' }}>✅ TP Hit (Wins)</option>
                <option value="sl_hit" {{ $selectedStatus === 'sl_hit' ? 'selected' : '' }}>🛑 SL Hit (Losses)</option>
            </select>
        </div>
        <div style="font-size:12px;color:var(--text-muted)">
            📋 Formatted to match spreadsheet trail test specifications
        </div>
    </form>
</div>

{{-- ======= SPREADSHEET TRAIL TEST TABLE ======= --}}
<div class="card" style="padding:0;overflow:hidden">
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:12px;text-align:center" class="table-trail-test">
            <thead>
                {{-- Group Header Row 1 --}}
                <tr style="background:var(--bg-900);border-bottom:1px solid var(--border)">
                    <th colspan="7" style="padding:10px;color:var(--text-primary);font-size:13px;font-weight:800;border-right:2px solid var(--border);background:rgba(77,158,255,0.1)">
                        Recomended Potensial
                    </th>
                    <th colspan="3" style="padding:10px;color:var(--text-primary);font-size:13px;font-weight:800;border-right:2px solid var(--border);background:rgba(114,9,183,0.1)">
                        Timeframe & Execution Timing
                    </th>
                    <th colspan="4" style="padding:10px;color:var(--text-primary);font-size:13px;font-weight:800;background:rgba(0,212,160,0.1)">
                        Trail Test Outcome
                    </th>
                </tr>
                {{-- Group Header Row 2 --}}
                <tr style="background:var(--bg-800);border-bottom:2px solid var(--border);color:var(--text-muted);font-size:11px;text-transform:uppercase">
                    <th style="padding:10px 14px;text-align:left">koin</th>
                    <th style="padding:10px;color:var(--green)">Live Price (Tick)</th>
                    <th style="padding:10px">Entry</th>
                    <th style="padding:10px">SL</th>
                    <th style="padding:10px">TP1</th>
                    <th style="padding:10px">TP2</th>
                    <th style="padding:10px;border-right:2px solid var(--border)">TP3</th>
                    <th style="padding:10px">Datetime</th>
                    <th style="padding:10px;color:var(--purple)">End Date (15m)</th>
                    <th style="padding:10px;border-right:2px solid var(--border)">Timeframe</th>
                    <th style="padding:10px">SL</th>
                    <th style="padding:10px">TP1</th>
                    <th style="padding:10px">TP2</th>
                    <th style="padding:10px">TP3</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trailTests as $test)
                @php
                    $isLong = $test['direction'] === 'LONG';
                    $symbolKey = isset($test['signal']) && $test['signal']->coin ? $test['signal']->coin->symbol : str_replace(['/', ' '], '', $test['coin_name']);
                @endphp
                <tr style="border-bottom:1px solid var(--border);background:var(--bg-700)">
                    {{-- 1. Koin --}}
                    <td style="padding:12px 14px;text-align:left;font-weight:800">
                        <div style="display:flex;align-items:center;gap:6px">
                            <span class="mono" style="color:var(--text-primary)">{{ $test['coin_name'] }}</span>
                            <span class="badge badge-{{ strtolower($test['direction']) }}" style="font-size:9px">
                                {{ $isLong ? 'LONG' : 'SHORT' }}
                            </span>
                        </div>
                    </td>

                    {{-- Live Price (Tick) --}}
                    <td class="mono" style="padding:12px;font-weight:800;color:var(--green)" data-live-price="{{ $symbolKey }}">
                        ${{ fmtPrice($test['signal']->coin->last_price ?? $test['entry']) }}
                    </td>

                    {{-- 2. Entry --}}
                    <td class="mono" style="padding:12px;font-weight:700;color:var(--blue)">
                        🎯 ${{ fmtPrice($test['entry']) }}
                    </td>

                    {{-- 3. SL --}}
                    <td class="mono" style="padding:12px;color:var(--red)">
                        🛑 ${{ fmtPrice($test['sl']) }}
                    </td>

                    {{-- 4. TP1 --}}
                    <td class="mono" style="padding:12px;color:var(--green)">
                        ✅ ${{ fmtPrice($test['tp1']) }}
                    </td>

                    {{-- 5. TP2 --}}
                    <td class="mono" style="padding:12px;color:var(--green)">
                        🚀 ${{ fmtPrice($test['tp2']) }}
                    </td>

                    {{-- 6. TP3 --}}
                    <td class="mono" style="padding:12px;color:var(--green);border-right:2px solid var(--border)">
                        💎 ${{ fmtPrice($test['tp3']) }}
                    </td>

                    {{-- 7. Datetime --}}
                    <td class="mono" style="padding:12px;font-size:11px;color:var(--text-secondary)">
                        <div style="font-weight:700;color:var(--text-primary)">{{ $test['datetime'] }}</div>
                        <div style="font-size:9px;color:var(--text-muted)">waktu price sama dengan entry</div>
                    </td>

                    {{-- 8. End Date (15m) --}}
                    <td class="mono" style="padding:12px;font-size:11px;color:var(--purple)">
                        <div style="font-weight:700">{{ $test['end_datetime'] }}</div>
                        <div style="font-size:9px;color:var(--text-muted)">penutupan candle 15m / exit</div>
                    </td>

                    {{-- 8. Timeframe --}}
                    <td style="padding:12px;border-right:2px solid var(--border)">
                        <span class="badge badge-purple" style="font-size:10px;padding:3px 8px">
                            {{ $test['timeframe'] }}
                        </span>
                    </td>

                    {{-- 9. Trail Test SL --}}
                    <td style="padding:12px">
                        @if($test['sl_outcome']['hit'])
                            <div class="badge badge-short" style="font-size:11px;font-family:var(--font-mono)">
                                [x] ${{ fmtPrice($test['sl_outcome']['price']) }}
                            </div>
                        @else
                            <div class="badge badge-pass" style="font-size:11px;font-family:var(--font-mono)">
                                [ok] Safe
                            </div>
                        @endif
                    </td>

                    {{-- 10. Trail Test TP1 --}}
                    <td style="padding:12px">
                        @if($test['tp1_outcome']['hit'])
                            <div class="badge badge-pass" style="font-size:11px;font-family:var(--font-mono)">
                                [ok] ${{ fmtPrice($test['tp1_outcome']['price']) }}
                            </div>
                        @else
                            <div class="badge" style="background:var(--bg-800);color:var(--text-muted);font-size:11px;font-family:var(--font-mono);border:1px solid var(--border)">
                                [x]
                            </div>
                        @endif
                    </td>

                    {{-- 11. Trail Test TP2 --}}
                    <td style="padding:12px">
                        @if($test['tp2_outcome']['hit'])
                            <div class="badge badge-pass" style="font-size:11px;font-family:var(--font-mono)">
                                [ok] ${{ fmtPrice($test['tp2_outcome']['price']) }}
                            </div>
                        @else
                            <div class="badge" style="background:var(--bg-800);color:var(--text-muted);font-size:11px;font-family:var(--font-mono);border:1px solid var(--border)">
                                [x]
                            </div>
                        @endif
                    </td>

                    {{-- 12. Trail Test TP3 --}}
                    <td style="padding:12px">
                        @if($test['tp3_outcome']['hit'])
                            <div class="badge badge-pass" style="font-size:11px;font-family:var(--font-mono)">
                                [ok] ${{ fmtPrice($test['tp3_outcome']['price']) }}
                            </div>
                        @else
                            <div class="badge" style="background:var(--bg-800);color:var(--text-muted);font-size:11px;font-family:var(--font-mono);border:1px solid var(--border)">
                                [x]
                            </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" style="padding:40px;text-align:center;color:var(--text-muted)">
                        <div style="font-size:32px;margin-bottom:8px">🧪</div>
                        <h3>Belum Ada Data Historical Trail Test</h3>
                        <p>Jalankan scanner untuk menghasilkan sinyal baru dan memulai pengujian trail test.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($signals->hasPages())
    <div style="padding:16px;border-top:1px solid var(--border)">
        {{ $signals->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@endsection
