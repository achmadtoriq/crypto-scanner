@extends('layouts.app')

@section('title', $coin->symbol)
@section('page-title', '🪙 ' . $coin->base_asset . '/USDT')
@section('page-subtitle', $coin->symbol . ' — ' . strtoupper($coin->exchange) . ' · Updated ' . ($coin->last_fetched_at ? $coin->last_fetched_at->diffForHumans() : 'never'))

@section('topbar-actions')
    <a href="{{ route('coins.index') }}" class="btn btn-ghost">← Back</a>
    <a href="https://www.tradingview.com/chart/?symbol=BINANCE:{{ $coin->symbol }}" target="_blank" class="btn btn-ghost">📈 TradingView</a>
@endsection

@section('content')

@php $ind = $coin->latestIndicator; @endphp

{{-- ======= COIN HEADER STATS ======= --}}
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card blue">
        <div class="stat-label">Last Price</div>
        <div class="stat-value mono" style="font-size:22px" data-live-price="{{ $coin->symbol }}">${{ fmtPrice($coin->last_price) }}</div>
        <div class="stat-change">USDT · Binance</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Volume 24h</div>
        <div class="stat-value" style="font-size:22px">{{ $coin->formatted_volume }}</div>
        <div class="stat-change">Quote USDT</div>
    </div>
    <div class="stat-card {{ $ind && $ind->is_rsi_healthy ? 'green' : 'purple' }}">
        <div class="stat-label">RSI (14)</div>
        <div class="stat-value mono" style="font-size:22px" data-live-rsi>
            {{ $ind && $ind->rsi ? number_format((float)$ind->rsi, 1) : '—' }}
        </div>
        <div class="stat-change">
            @if($ind && $ind->rsi)
                {{ $ind->is_rsi_healthy ? '✅ Bullish Zone' : ((float)$ind->rsi >= 70 ? '⚠️ Overbought' : ((float)$ind->rsi <= 30 ? '⚠️ Oversold' : 'Neutral')) }}
            @endif
        </div>
    </div>
    <div class="stat-card {{ $ind && $ind->is_volume_spike ? 'gold' : '' }}">
        <div class="stat-label">Volume Spike</div>
        <div class="stat-value mono" style="font-size:22px">
            {{ $ind && $ind->volume_spike ? number_format((float)$ind->volume_spike, 2) . 'x' : '—' }}
        </div>
        <div class="stat-change">
            @if($ind) {{ $ind->is_volume_spike ? '🔥 Spike detected!' : 'vs 20 candle avg' }} @endif
        </div>
    </div>
    @if($ind && isset($ind->macd_histogram))
    <div class="stat-card {{ (float)$ind->macd_histogram > 0 ? 'green' : 'red' }}">
        <div class="stat-label">MACD</div>
        <div class="stat-value mono" style="font-size:18px;color:{{ (float)$ind->macd_histogram > 0 ? 'var(--green)' : 'var(--red)' }}">
            {{ (float)$ind->macd_histogram > 0 ? '⬆' : '⬇' }} {{ number_format(abs((float)$ind->macd_histogram), 6) }}
        </div>
        <div class="stat-change">{{ (float)$ind->macd_histogram > 0 ? 'Bullish Crossover' : 'Bearish Crossover' }}</div>
    </div>
    @endif
</div>

{{-- ======= MULTI-TIMEFRAME ALIGNMENT MATRIX ======= --}}
@php
    $ind15m = $coin->indicators()->where('interval', '15m')->latest('calculated_at')->first();
    $ind1h  = $coin->indicators()->where('interval', '1h')->latest('calculated_at')->first() ?? $ind;
    $ind4h  = $coin->indicators()->where('interval', '4h')->latest('calculated_at')->first();

    $price  = (float)($coin->last_price ?? 0);

    // 15M Micro Momentum: evaluate actual 15m indicator MACD & EMA
    if ($ind15m) {
        $macdH15m = (float)($ind15m->macd_histogram ?? 0);
        $ema9_15m = (float)($ind15m->ema9 ?? 0);
        $tf15m    = ($macdH15m > 0 && ($ema9_15m > 0 ? $price > $ema9_15m : true)) ? 'BULLISH 🟢' : 'BEARISH 🔴';
    } else {
        $macdH1h = $ind1h && isset($ind1h->macd_histogram) ? (float)$ind1h->macd_histogram : 0;
        $tf15m   = ($macdH1h > 0) ? 'BULLISH 🟢' : 'BEARISH 🔴';
    }

    // 1H Primary Trend
    $ma20_1h = $ind1h ? (float)$ind1h->ma20 : 0;
    $ma50_1h = $ind1h ? (float)$ind1h->ma50 : 0;
    $tf1h    = ($price > $ma20_1h && ($ma50_1h > 0 ? $ma20_1h > $ma50_1h : true)) ? 'BULLISH 🟢' : 'BEARISH 🔴';

    // 4H & 1D Trend
    $rsi1h = $ind1h ? (float)$ind1h->rsi : 50;
    $tf4h  = ($ind4h ? (float)$ind4h->rsi >= 50 : $rsi1h >= 52) ? 'BULLISH 🟢' : 'BEARISH 🔴';
    $tf1d  = ($rsi1h >= 55) ? 'BULLISH 🟢' : 'CONSOLIDATING 🟡';

    // EMA & MACD Cards
    $ema9  = $ind1h && isset($ind1h->ema9) ? (float)$ind1h->ema9 : 0;
    $ema21 = $ind1h && isset($ind1h->ema21) ? (float)$ind1h->ema21 : 0;
    $tfEma = ($ema9 > 0 && $ema21 > 0 && $ema9 > $ema21 && $price > $ema9) ? 'GOLDEN CROSS 🌟' : ($ema9 > 0 && $ema21 > 0 && $ema9 < $ema21 ? 'DEATH CROSS 💀' : 'NEUTRAL ~');

    $macdH  = $ind15m ? (float)($ind15m->macd_histogram ?? 0) : ($ind1h ? (float)($ind1h->macd_histogram ?? 0) : 0);
    $tfMacd = $macdH > 0 ? 'BULLISH ⬆' : 'BEARISH ⬇';

    $bullCount = collect([$tf15m,$tf1h,$tf4h,$tf1d,$tfMacd])->filter(fn($v) => str_contains($v,'BULLISH'))->count();
    $isUltraHighProb = $bullCount >= 4;
@endphp

<div class="card mb-4" style="background:var(--bg-700);border:1px solid {{ $isUltraHighProb ? 'rgba(0,212,160,0.5)' : 'var(--border)' }}">
    <div class="card-header">
        <div class="card-title">📊 Multi-Timeframe Alignment Matrix ({{ $coin->base_asset }}/USDT)</div>
        @if($isUltraHighProb)
            <span class="badge badge-pass">🔥 ULTRA-HIGH PROBABILITY ({{ $bullCount }}/5 Bullish)</span>
        @else
            <span class="badge badge-blue">{{ $bullCount }}/5 Bullish</span>
        @endif
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px">
            @foreach([
                ['15m', $tf15m, 'Micro Momentum'],
                ['1h', $tf1h, 'Primary Trend'],
                ['4h', $tf4h, 'Macro Structure'],
                ['1d', $tf1d, 'Daily Trend'],
                ['EMA 9/21', $tfEma, 'EMA Crossover'],
                ['MACD', $tfMacd, '12/26/9'],
            ] as [$label, $val, $sub])
            @php
                $isBullColor = str_contains($val,'BULLISH') || str_contains($val,'GOLDEN') || str_contains($val,'⬆');
                $isBearColor = str_contains($val,'BEARISH') || str_contains($val,'DEATH') || str_contains($val,'⬇');
                $dotColor    = $isBullColor ? 'var(--green)' : ($isBearColor ? 'var(--red)' : 'var(--yellow)');
            @endphp
            <div style="background:var(--bg-800);padding:12px;border-radius:10px;border:1px solid {{ $isBullColor ? 'rgba(0,212,160,0.2)' : ($isBearColor ? 'rgba(255,77,109,0.2)' : 'var(--border)') }};text-align:center">
                <div style="font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">{{ $label }}</div>
                <div style="font-size:12px;font-weight:800;margin-top:6px;color:{{ $dotColor }}">{{ $val }}</div>
            </div>
            @endforeach
        </div>

        @if(str_contains($tf15m, 'BULLISH') && str_contains($tf1h, 'BEARISH'))
            <div style="margin-top:14px;padding:12px 16px;background:rgba(255,183,3,0.12);border:1px solid rgba(255,183,3,0.4);border-radius:8px;font-size:12px;color:var(--yellow);display:flex;align-items:center;gap:10px">
                <span style="font-size:18px">⚠️</span>
                <div>
                    <strong style="color:#fff">Multi-Timeframe Conflict (15M Micro-Bounce vs 1H Bearish Trend):</strong><br>
                    Momentum 15M saat ini sedang mengalami pantulan naik (🟢 Bullish 15M), namun Tren Utama 1-Jam masih 🔴 Bearish.<br>
                    <em>Panduan Trading:</em> Jika Anda mengambil sinyal <strong>SHORT 1H</strong> dari Dashboard, tunggu hingga pantulan 15M melemah di dekat Support/Resistance sebelum memasukkan order Jual (SHORT)!
                </div>
            </div>
        @elseif(str_contains($tf15m, 'BEARISH') && str_contains($tf1h, 'BULLISH'))
            <div style="margin-top:14px;padding:12px 16px;background:rgba(77,158,255,0.12);border:1px solid rgba(77,158,255,0.4);border-radius:8px;font-size:12px;color:var(--blue);display:flex;align-items:center;gap:10px">
                <span style="font-size:18px">💡</span>
                <div>
                    <strong style="color:#fff">Multi-Timeframe Pullback (15M Dip vs 1H Bullish Trend):</strong><br>
                    Momentum 15M sedang mengalami koreksi sementara (🔴 Bearish 15M), namun Tren Utama 1-Jam masih 🟢 Bullish.<br>
                    <em>Panduan Trading:</em> Ini adalah area koreksi sehat ("Buy the Dip") yang sangat bagus untuk persiapan <strong>LONG</strong>!
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ======= SUPPORT & RESISTANCE ======= --}}
@if(!empty($supportResistance) && ($supportResistance['support'] || $supportResistance['resistance']))
<div class="card mb-4" style="background:var(--bg-700);border:1px solid rgba(77,158,255,0.3)">
    <div class="card-header">
        <div class="card-title">🎯 Key Support & Resistance Levels (20-Period)</div>
        <span class="badge badge-blue">Based on Recent Klines</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;align-items:center">
            <div style="text-align:center;padding:14px;background:var(--red-dim);border:1px solid rgba(255,77,109,0.3);border-radius:10px">
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase">🛡️ Key Support</div>
                <div style="font-size:20px;font-weight:800;font-family:var(--font-mono);color:var(--red);margin-top:6px">
                    ${{ fmtPrice($supportResistance['support']) }}
                </div>
                <div id="sr-sup-dist" style="font-size:11px;color:var(--text-muted);margin-top:4px">
                    @if($supportResistance['support'])
                        {{ number_format((($price - $supportResistance['support']) / $price) * 100, 2) }}% below current
                    @endif
                </div>
            </div>
            <div style="text-align:center;padding:14px;background:var(--bg-800);border:1px solid var(--border);border-radius:10px">
                <div style="font-size:10px;color:var(--text-muted)">Current Price</div>
                <div style="font-size:20px;font-weight:800;font-family:var(--font-mono);color:var(--text-primary);margin-top:6px">${{ fmtPrice($coin->last_price) }}</div>
                @php
                    $midRange = $supportResistance['support'] && $supportResistance['resistance'] ?
                        (($price - $supportResistance['support']) / ($supportResistance['resistance'] - $supportResistance['support'])) * 100 : 50;
                @endphp
                <div style="height:6px;background:var(--bg-500);border-radius:3px;margin-top:10px;overflow:hidden">
                    <div style="height:100%;width:{{ min(100, max(0, round($midRange))) }}%;background:linear-gradient(90deg,var(--green),var(--yellow));border-radius:3px"></div>
                </div>
                <div style="font-size:10px;color:var(--text-muted);margin-top:4px">{{ round($midRange) }}% in range</div>
            </div>
            <div style="text-align:center;padding:14px;background:var(--green-dim);border:1px solid rgba(0,212,160,0.3);border-radius:10px">
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase">🏔️ Key Resistance</div>
                <div style="font-size:20px;font-weight:800;font-family:var(--font-mono);color:var(--green);margin-top:6px">
                    ${{ fmtPrice($supportResistance['resistance']) }}
                </div>
                <div id="sr-res-dist" style="font-size:11px;color:var(--text-muted);margin-top:4px">
                    @if($supportResistance['resistance'])
                        {{ number_format((($supportResistance['resistance'] - $price) / $price) * 100, 2) }}% above current
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ======= MULTI-EXCHANGE COMPARISON MATRIX ======= --}}
@if(!empty($comparison))
<div class="card mb-4" style="background:var(--bg-700);border:1px solid rgba(102,126,234,0.3)">
    <div class="card-header">
        <div class="card-title">🌐 Multi-Exchange Comparison ({{ $coin->base_asset }}/USDT)</div>
        <span class="badge badge-purple">Binance vs Bybit vs OKX</span>
    </div>
    <div class="card-body">
        @if(!empty($spreadInsight))
        <div style="background:var(--bg-800);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Cross-Exchange Spread</div>
                <div style="font-size:18px;font-weight:800;font-family:var(--font-mono);color:{{ $spreadInsight['is_opportunity'] ? 'var(--green)' : 'var(--text-primary)' }}">
                    ${{ fmtPrice($spreadInsight['diff_usdt']) }} ({{ number_format($spreadInsight['diff_pct'], 2) }}%)
                </div>
            </div>
            <div style="font-size:12px;color:var(--text-secondary)">
                Highest: <strong style="color:var(--yellow)">{{ $spreadInsight['max_ex'] }}</strong> (${{ fmtPrice($spreadInsight['max_price']) }}) &bull;
                Lowest: <strong style="color:var(--blue)">{{ $spreadInsight['min_ex'] }}</strong> (${{ fmtPrice($spreadInsight['min_price']) }})
            </div>
            <div>
                @if($spreadInsight['is_opportunity'])
                    <span class="badge badge-pass" style="font-size:13px;padding:6px 12px">🔥 Arbitrage Opportunity!</span>
                @else
                    <span class="badge badge-blue" style="font-size:13px;padding:6px 12px">✅ Normal Spread</span>
                @endif
            </div>
        </div>
        @endif
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>Metric</th><th>🟡 BINANCE</th><th>🟠 BYBIT</th><th>🔵 OKX</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-semibold text-primary">Last Price ($)</td>
                        <td class="mono font-bold" style="color:var(--yellow)" data-live-price="{{ $coin->symbol }}">{{ $comparison['binance']['price'] ? '$'.fmtPrice($comparison['binance']['price']) : '—' }}</td>
                        <td class="mono font-bold" style="color:#ff9800" data-live-price-bybit="{{ $coin->symbol }}">{{ $comparison['bybit']['price'] ? '$'.fmtPrice($comparison['bybit']['price']) : '—' }}</td>
                        <td class="mono font-bold" style="color:var(--blue)" data-live-price-okx="{{ $coin->symbol }}">{{ $comparison['okx']['price'] ? '$'.fmtPrice($comparison['okx']['price']) : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-primary">Volume 24h</td>
                        <td class="mono">{{ $comparison['binance']['volume_24h'] }}</td>
                        <td class="mono">{{ $comparison['bybit']['volume_24h'] }}</td>
                        <td class="mono">{{ $comparison['okx']['volume_24h'] }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-primary">Price Change 24h</td>
                        <td class="mono" style="color:{{ (float)$comparison['binance']['price_change'] >= 0 ? 'var(--green)' : 'var(--red)' }}">{{ is_numeric($comparison['binance']['price_change']) ? number_format((float)$comparison['binance']['price_change'], 2).'%' : '—' }}</td>
                        <td class="mono" style="color:{{ (float)$comparison['bybit']['price_change'] >= 0 ? 'var(--green)' : 'var(--red)' }}">{{ is_numeric($comparison['bybit']['price_change']) ? number_format((float)$comparison['bybit']['price_change'], 2).'%' : '—' }}</td>
                        <td class="mono" style="color:{{ (float)$comparison['okx']['price_change'] >= 0 ? 'var(--green)' : 'var(--red)' }}">{{ is_numeric($comparison['okx']['price_change']) ? number_format((float)$comparison['okx']['price_change'], 2).'%' : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-primary">RSI (14)</td>
                        <td class="mono">{{ is_numeric($comparison['binance']['rsi']) ? number_format((float)$comparison['binance']['rsi'], 1) : '—' }}</td>
                        <td class="mono">{{ is_numeric($comparison['bybit']['rsi']) ? number_format((float)$comparison['bybit']['rsi'], 1) : '—' }}</td>
                        <td class="mono">{{ is_numeric($comparison['okx']['rsi']) ? number_format((float)$comparison['okx']['rsi'], 1) : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-primary">Orderbook Spread</td>
                        <td class="mono text-muted">{{ $comparison['binance']['spread'] }}%</td>
                        <td class="mono text-muted">{{ $comparison['bybit']['spread'] }}%</td>
                        <td class="mono text-muted">{{ $comparison['okx']['spread'] }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ======= TRADINGVIEW CHART ======= --}}
<div class="card mb-4">
    <div class="card-header">
        <div class="card-title">📈 Live TradingView Chart ({{ $coin->symbol }})</div>
        <span class="badge badge-blue">Real-time</span>
    </div>
    <div class="card-body" style="padding:0">
        <div class="tradingview-widget-container" style="height:480px;width:100%">
            <div id="tradingview_coin_chart" style="height:100%;width:100%"></div>
            <script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
            <script type="text/javascript">
            new TradingView.widget({
                "autosize": true,
                "symbol": "BINANCE:{{ $coin->symbol }}",
                "interval": "{{ ($selectedInterval ?? '1h') === '15m' ? '15' : '60' }}",
                "timezone": "Asia/Jakarta",
                "theme": "dark",
                "style": "1",
                "locale": "en",
                "toolbar_bg": "#0a0b0f",
                "enable_publishing": false,
                "hide_side_toolbar": false,
                "allow_symbol_change": true,
                "studies": ["RSI@tv-basicstudies", "MACD@tv-basicstudies"],
                "container_id": "tradingview_coin_chart"
            });
            </script>
        </div>
    </div>
</div>

{{-- ======= INDICATORS + ADVANCED ======= --}}
<div class="grid-2">
    {{-- Technical Indicators Panel --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">📊 Technical Indicators</div>
            @if($ind)
                <span class="text-sm text-muted">{{ $ind->calculated_at->diffForHumans() }}</span>
            @endif
        </div>
        @if(!$ind)
            <div class="empty-state" style="padding:30px">
                <div class="icon">📊</div>
                <h3>No indicator data</h3>
                <p>Run the scanner to calculate indicators</p>
            </div>
        @else
        <div class="card-body">
            @php
            $rows = [
                ['MA20', $ind->ma20 ? fmtPrice($ind->ma20) : '—', $ind->is_above_ma20 ? 'var(--green)' : 'var(--red)', $ind->is_above_ma20 ? '↑ Price above' : '↓ Price below'],
                ['MA50', $ind->ma50 ? fmtPrice($ind->ma50) : '—', $ind->is_above_ma50 ? 'var(--green)' : 'var(--red)', $ind->is_above_ma50 ? '↑ Price above' : '↓ Price below'],
                ['EMA 9', isset($ind->ema9) && $ind->ema9 ? fmtPrice($ind->ema9) : '—', isset($ind->is_above_ema9) && $ind->is_above_ema9 ? 'var(--green)' : 'var(--red)', ''],
                ['EMA 21', isset($ind->ema21) && $ind->ema21 ? fmtPrice($ind->ema21) : '—', isset($ind->is_above_ema21) && $ind->is_above_ema21 ? 'var(--green)' : 'var(--red)', ''],
                ['RSI (14)', $ind->rsi ? number_format((float)$ind->rsi, 2) : '—', $ind->is_rsi_healthy ? 'var(--green)' : 'var(--text-secondary)', ''],
                ['Stoch %K', $ind->stoch_k ? number_format((float)$ind->stoch_k, 2) : '—', 'var(--text-primary)', ''],
                ['Stoch %D', $ind->stoch_d ? number_format((float)$ind->stoch_d, 2) : '—', 'var(--text-primary)', ''],
                ['ATR', $ind->atr ? number_format((float)$ind->atr, 6) : '—', 'var(--text-primary)', ''],
                ['Price Chg', $ind->price_change_pct ? number_format((float)$ind->price_change_pct, 2).'%' : '—', (float)$ind->price_change_pct >= 0 ? 'var(--green)' : 'var(--red)', ''],
                ['Spread', $ind->spread_pct ? number_format((float)$ind->spread_pct, 4).'%' : '—', 'var(--text-secondary)', ''],
            ];
            @endphp
            @foreach($rows as [$label, $val, $color, $hint])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
                <span class="text-muted" style="font-size:12px">{{ $label }}</span>
                <div style="text-align:right">
                    <span style="font-family:var(--font-mono);font-size:13px;font-weight:600;color:{{ $color }}">{{ $val }}</span>
                    @if($hint)<span style="font-size:10px;color:var(--text-muted);display:block">{{ $hint }}</span>@endif
                </div>
            </div>
            @endforeach

            {{-- MACD Section --}}
            @if(isset($ind->macd_line) && $ind->macd_line !== null)
            <div style="margin-top:12px;padding:12px;background:var(--bg-800);border-radius:8px;border:1px solid var(--border)">
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">MACD (12, 26, 9)</div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;font-size:11px">
                    <div>
                        <div style="color:var(--text-muted)">Line</div>
                        <div class="mono" style="color:var(--text-primary);font-weight:600">{{ number_format((float)$ind->macd_line, 6) }}</div>
                    </div>
                    <div>
                        <div style="color:var(--text-muted)">Signal</div>
                        <div class="mono" style="color:var(--text-primary);font-weight:600">{{ number_format((float)$ind->macd_signal, 6) }}</div>
                    </div>
                    <div>
                        <div style="color:var(--text-muted)">Histogram</div>
                        <div class="mono" style="color:{{ (float)$ind->macd_histogram > 0 ? 'var(--green)' : 'var(--red)' }};font-weight:700">
                            {{ (float)$ind->macd_histogram > 0 ? '+' : '' }}{{ number_format((float)$ind->macd_histogram, 6) }}
                        </div>
                    </div>
                </div>
                <div style="margin-top:8px">
                    @if($ind->is_macd_bullish)
                        <span class="badge badge-pass" style="font-size:10px">⬆ Bullish Crossover</span>
                    @else
                        <span class="badge badge-short" style="font-size:10px">⬇ Bearish Crossover</span>
                    @endif
                </div>
            </div>
            @endif

            {{-- Bollinger Bands --}}
            @if(isset($ind->bb_upper) && $ind->bb_upper !== null)
            <div style="margin-top:10px;padding:12px;background:var(--bg-800);border-radius:8px;border:1px solid var(--border)">
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">Bollinger Bands (20, 2σ)</div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;font-size:11px">
                    <div><div style="color:var(--red)">Upper</div><div class="mono" style="font-weight:600">${{ fmtPrice($ind->bb_upper) }}</div></div>
                    <div><div style="color:var(--text-muted)">Middle</div><div class="mono" style="font-weight:600">${{ fmtPrice(($ind->bb_upper + $ind->bb_lower) / 2) }}</div></div>
                    <div><div style="color:var(--blue)">Lower</div><div class="mono" style="font-weight:600">${{ fmtPrice($ind->bb_lower) }}</div></div>
                </div>
                @php $bbPct = isset($ind->bb_pct_b) ? (float)$ind->bb_pct_b : 50; @endphp
                <div style="margin-top:10px">
                    <div style="font-size:10px;color:var(--text-muted);margin-bottom:4px">%B Position: {{ round($bbPct) }}%</div>
                    <div style="height:6px;background:var(--bg-500);border-radius:3px;overflow:hidden;position:relative">
                        <div style="position:absolute;left:0;height:100%;width:20%;background:var(--blue-dim)"></div>
                        <div style="position:absolute;right:0;height:100%;width:20%;background:var(--red-dim)"></div>
                        <div style="position:absolute;height:100%;width:4px;background:var(--yellow);border-radius:2px;left:{{ min(96, max(0, $bbPct)) }}%;transform:translateX(-50%)"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:9px;color:var(--text-muted);margin-top:3px">
                        <span>Lower Band</span>
                        @if(isset($ind->is_bb_squeeze) && $ind->is_bb_squeeze)
                            <span style="color:var(--yellow)">🔥 SQUEEZE - Breakout Imminent!</span>
                        @endif
                        <span>Upper Band</span>
                    </div>
                </div>
            </div>
            @endif

            <div style="margin-top:16px;display:flex;flex-wrap:wrap;gap:6px">
                @if($ind->is_above_ma20) <span class="badge badge-pass">↑ Above MA20</span> @endif
                @if($ind->is_above_ma50) <span class="badge badge-pass">↑ Above MA50</span> @endif
                @if(isset($ind->is_above_ema9) && $ind->is_above_ema9) <span class="badge badge-pass">↑ Above EMA9</span> @endif
                @if(isset($ind->is_macd_bullish) && $ind->is_macd_bullish) <span class="badge badge-pass">⬆ MACD Bull</span> @endif
                @if($ind->is_volume_spike) <span class="badge badge-blue">🔥 Vol Spike</span> @endif
                @if($ind->is_oi_increasing) <span class="badge badge-blue">↑ OI Rising</span> @endif
                @if($ind->is_rsi_healthy) <span class="badge badge-pass">✅ RSI Healthy</span> @endif
                @if(isset($ind->is_bb_squeeze) && $ind->is_bb_squeeze) <span class="badge badge-gold">🔥 BB Squeeze</span> @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Signal History --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">⚡ Signal History</div>
            <a href="{{ route('signals.index', ['coin' => $coin->base_asset]) }}" class="btn btn-ghost btn-sm">View All</a>
        </div>
        @php $signals = isset($signalHistory) ? $signalHistory : $coin->signals()->latest()->limit(5)->get(); @endphp
        @if($signals->isEmpty())
            <div class="empty-state" style="padding:30px">
                <div class="icon">📭</div>
                <h3>No signals yet</h3>
                <p>Run the scanner to generate signals for this coin</p>
            </div>
        @else
            <div style="padding:0">
                @foreach($signals as $s)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-bottom:1px solid var(--border)">
                    <div style="display:flex;align-items:center;gap:10px">
                        <span class="badge badge-{{ strtolower($s->direction) }}">{{ $s->direction }}</span>
                        <div>
                            <div class="mono" style="font-size:13px;font-weight:700">${{ fmtPrice($s->entry_price) }}</div>
                            <div style="font-size:10px;color:var(--text-muted)">{{ $s->created_at->format('M d, H:i') }} · {{ $s->confidence_score }}% conf</div>
                        </div>
                    </div>
                    <div style="text-align:right">
                        <span class="badge badge-{{ $s->outcome_badge_color }}" style="font-size:10px">{{ $s->outcome_label }}</span>
                        @if($s->pnl_pct !== null)
                            <div class="mono" style="font-size:11px;margin-top:3px;color:{{ $s->pnl_pct >= 0 ? 'var(--green)' : 'var(--red)' }}">
                                {{ $s->pnl_pct >= 0 ? '+' : '' }}{{ $s->pnl_pct }}%
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const symbol = "{{ $coin->symbol }}";

    // 1. Sync 15m klines for 15M Micro Momentum accuracy
    fetch(`https://api.binance.com/api/v3/klines?symbol=${symbol}&interval=15m&limit=60`)
        .then(r => r.json())
        .then(klines => {
            if (!Array.isArray(klines) || klines.length === 0) return;
            fetch('{{ route("api.sync-klines") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ symbol: symbol, interval: '15m', klines: klines })
            });
        }).catch(err => console.debug('15m klines fetch:', err));

    // 2. Sync 1h klines for 1h indicators & RSI
    fetch(`https://api.binance.com/api/v3/klines?symbol=${symbol}&interval=1h&limit=60`)
        .then(r => r.json())
        .then(klines => {
            if (!Array.isArray(klines) || klines.length === 0) return;
            fetch('{{ route("api.sync-klines") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ symbol: symbol, interval: '1h', klines: klines })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.rsi !== null) {
                    document.querySelectorAll('[data-live-rsi]').forEach(el => {
                        el.textContent = res.rsi;
                    });
                    console.log('Real Binance Klines & RSI Synced:', res.rsi);
                }
            });
        })
        .catch(err => console.debug('1h klines fetch:', err));
});
</script>
@if(!empty($supportResistance))
<script>
window.addEventListener('binance-tick', (e) => {
    if (e.detail.symbol !== "{{ $coin->symbol }}") return;
    const curP = e.detail.price;
    const sup = {{ (float)($supportResistance['support'] ?? 0) }};
    const res = {{ (float)($supportResistance['resistance'] ?? 0) }};
    
    const supEl = document.getElementById('sr-sup-dist');
    if (supEl && sup > 0) {
        const pct = ((curP - sup) / curP) * 100;
        supEl.textContent = pct.toFixed(2) + '% below current';
    }
    const resEl = document.getElementById('sr-res-dist');
    if (resEl && res > 0) {
        const pct = ((res - curP) / curP) * 100;
        resEl.textContent = pct.toFixed(2) + '% above current';
    }
});
</script>
@endif
@endpush

@endsection
