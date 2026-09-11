@extends('layouts.app')

@section('title', 'Scan Logs')
@section('page-title', '📋 Scan Logs')
@section('page-subtitle', 'Real-time scanner activity')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <form method="GET" action="{{ route('logs.index') }}" class="filter-bar" style="margin-bottom:0">
        <select name="stage" class="form-select">
            <option value="">All Stages</option>
            @foreach(['fetch','calculate','screen','analyze','signal','notify','info'] as $s)
                <option value="{{ $s }}" {{ request('stage')===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <select name="status" class="form-select">
            <option value="">All Status</option>
            @foreach(['pass','skip','error','info'] as $s)
                <option value="{{ $s }}" {{ request('status')===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        @if($sessions->isNotEmpty())
        <select name="session_id" class="form-select">
            <option value="">All Sessions</option>
            @foreach($sessions as $sess)
                <option value="{{ $sess }}" {{ request('session_id')===$sess ? 'selected' : '' }}>
                    {{ substr($sess, 0, 8) }}...
                </option>
            @endforeach
        </select>
        @endif
        <input type="date" name="date" class="form-input" value="{{ request('date') }}" style="max-width:160px">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('logs.index') }}" class="btn btn-ghost">Reset</a>
    </form>

    <form method="POST" action="{{ route('logs.clear') }}" onsubmit="return confirm('Clear logs older than 7 days?')">
        @csrf @method('DELETE')
        <input type="hidden" name="older_than_days" value="7">
        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red)">🗑 Clear Old Logs</button>
    </form>
</div>

<div class="card">
    @if($logs->isEmpty())
        <div class="empty-state">
            <div class="icon">📜</div>
            <h3>No logs found</h3>
            <p>Scanner activity will appear here after running</p>
        </div>
    @else
        <div class="log-terminal" style="border:none;border-radius:0;max-height:600px">
            @foreach($logs as $log)
            <div class="log-line {{ $log->status }}" title="{{ $log->session_id ?? '' }}">
                <span class="log-time">{{ $log->created_at->format('m-d H:i:s') }}</span>
                <span class="log-stage">{{ $log->stage }}</span>
                @if($log->coin)
                    <a href="{{ route('coins.show', $log->coin_id) }}" class="log-coin" style="color:var(--purple)">
                        {{ $log->coin->symbol }}
                    </a>
                @else
                    <span class="log-coin" style="color:var(--text-muted)">SYSTEM</span>
                @endif
                <span class="log-msg">{{ $log->message }}</span>
                <span class="badge badge-{{ $log->status }}" style="flex-shrink:0;font-size:10px;padding:2px 6px">{{ $log->status }}</span>
            </div>
            @endforeach
        </div>
        <div class="pagination-wrapper">
            <div class="pagination-info">{{ $logs->total() }} total log entries</div>
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
