@extends('layouts.app')

@section('title', 'Coins')
@section('page-title', '🪙 Monitored Coins')
@section('page-subtitle', 'All USDT pairs tracked — sorted by opportunity')

@section('content')

<form method="GET" action="{{ route('coins.index') }}" class="filter-bar">
    <input type="text" name="search" placeholder="🔍 Search symbol..." class="form-input" value="{{ request('search') }}">
    <select name="sort" class="form-select">
        <option value=""          {{ request('sort')===''        ? 'selected' : '' }}>Sort: Default</option>
        <option value="rsi_high"  {{ request('sort')==='rsi_high'  ? 'selected' : '' }}>↑ RSI Highest</option>
        <option value="rsi_low"   {{ request('sort')==='rsi_low'   ? 'selected' : '' }}>↓ RSI Lowest</option>
        <option value="volume"    {{ request('sort')==='volume'    ? 'selected' : '' }}>Volume 24h</option>
        <option value="change"    {{ request('sort')==='change'    ? 'selected' : '' }}>% Change</option>
        <option value="bullish"   {{ request('sort')==='bullish'   ? 'selected' : '' }}>Trend: Bullish First</option>
    </select>
    <select name="trend" class="form-select">
        <option value="">All Trends</option>
        <option value="bullish" {{ request('trend')==='bullish' ? 'selected' : '' }}>🟢 Bullish</option>
        <option value="bearish" {{ request('trend')==='bearish' ? 'selected' : '' }}>🔴 Bearish</option>
        <option value="macd_bull" {{ request('trend')==='macd_bull' ? 'selected' : '' }}>⚡ MACD Bullish</option>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="{{ route('coins.index') }}" class="btn btn-ghost">Reset</a>
</form>

<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Symbol</th>
                    <th>Last Price</th>
                    <th>24h Change</th>
                    <th>Volume 24h</th>
                    <th>RSI</th>
                    <th>MACD</th>
                    <th>Trend</th>
                    <th>Vol Spike</th>
                    <th>BB %B</th>
                    <th>Signals 7d</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coins as $coin)
                @php
                    $ind = $coin->latestIndicator;
                    $chg = $ind ? (float)$ind->price_change_pct : 0;
                    $isBull = $ind && $ind->is_above_ma20 && $ind->is_above_ma50;
                    $isBear = $ind && !$ind->is_above_ma20 && !$ind->is_above_ma50;
                    $isMacdBull = $ind && isset($ind->is_macd_bullish) && $ind->is_macd_bullish;
                    $bbPctB = $ind && isset($ind->bb_pct_b) ? (float)$ind->bb_pct_b : null;
                    $signalCount7d = $coin->signals()->where('created_at', '>=', now()->subDays(7))->count();
                    $rowGlow = $isBull && $isMacdBull ? 'box-shadow:0 0 0 1px rgba(0,212,160,0.15) inset;' : ($isBear ? 'box-shadow:0 0 0 1px rgba(255,77,109,0.08) inset;' : '');
                @endphp
                <tr style="{{ $rowGlow }}">
                    <td>
                        <a href="{{ route('coins.show', $coin) }}" style="color:var(--blue);font-weight:700;font-size:14px">
                            {{ $coin->base_asset }}
                        </a>
                        <span style="color:var(--text-muted);font-size:11px">/USDT</span>
                        @if($isBull && $isMacdBull)
                            <span style="display:block;font-size:9px;color:var(--green);font-weight:700">🔥 HOT</span>
                        @endif
                    </td>
                    <td class="price mono" style="font-weight:700" data-live-price="{{ $coin->symbol }}">
                        ${{ fmtPrice($coin->last_price) }}
                    </td>
                    <td class="mono" style="color:{{ $chg >= 0 ? 'var(--green)' : 'var(--red)' }};font-weight:600" data-live-change="{{ $coin->symbol }}">
                        {{ $chg >= 0 ? '+' : '' }}{{ number_format($chg, 2) }}%
                    </td>
                    <td class="mono text-sm">{{ $coin->formatted_volume ?? '—' }}</td>
                    <td>
                        @if($ind && $ind->rsi)
                            @php $rsi = (float)$ind->rsi; @endphp
                            <span class="mono" style="
                                color:{{ $rsi >= 50 && $rsi <= 70 ? 'var(--green)' : ($rsi >= 70 ? 'var(--yellow)' : ($rsi <= 30 ? 'var(--red)' : 'var(--text-secondary)')) }};
                                font-weight:600;font-size:13px
                            ">{{ number_format($rsi, 1) }}</span>
                            <span style="font-size:10px;color:var(--text-muted);display:block">
                                {{ $rsi >= 70 ? 'OB' : ($rsi <= 30 ? 'OS' : ($rsi >= 50 ? '✓' : '~')) }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($ind && isset($ind->is_macd_bullish))
                            @if($ind->is_macd_bullish)
                                <span class="badge badge-pass" style="font-size:10px">⬆ Bull</span>
                            @else
                                <span class="badge badge-short" style="font-size:10px">⬇ Bear</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($ind)
                            @if($isBull)
                                <span class="badge badge-pass">🟢 Bullish</span>
                            @elseif($isBear)
                                <span class="badge badge-short">🔴 Bearish</span>
                            @else
                                <span class="badge badge-pending">~ Mixed</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($ind && $ind->volume_spike)
                            <span class="mono {{ $ind->is_volume_spike ? '' : 'text-muted' }}"
                                  style="{{ $ind->is_volume_spike ? 'color:var(--yellow);font-weight:700' : '' }}">
                                {{ number_format((float)$ind->volume_spike, 2) }}x
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($bbPctB !== null)
                            @php
                                $bbColor = $bbPctB > 80 ? 'var(--red)' : ($bbPctB < 20 ? 'var(--blue)' : 'var(--text-secondary)');
                                $bbLabel = $bbPctB > 80 ? 'Upper' : ($bbPctB < 20 ? 'Lower' : 'Mid');
                            @endphp
                            <span class="mono" style="color:{{ $bbColor }};font-size:12px">
                                {{ number_format($bbPctB, 0) }}%
                            </span>
                            <span style="font-size:10px;color:var(--text-muted);display:block">{{ $bbLabel }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($signalCount7d > 0)
                            <span class="badge badge-blue" style="font-size:10px">{{ $signalCount7d }} sig</span>
                        @else
                            <span class="text-muted" style="font-size:11px">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('coins.show', $coin) }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            <div class="empty-state">
                                <div class="icon">🪙</div>
                                <h3>No coins found</h3>
                                <p>Run <code>php artisan scanner:run</code> to populate coin data</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($coins->hasPages())
    <div class="pagination-wrapper">
        <div class="pagination-info">Showing {{ $coins->firstItem() }}–{{ $coins->lastItem() }} of {{ $coins->total() }} coins</div>
        {{ $coins->links() }}
    </div>
    @endif
</div>
@endsection
