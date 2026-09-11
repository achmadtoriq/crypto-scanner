<?php

namespace App\Http\Controllers;

use App\Models\ScanLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = ScanLog::with('coin')->latest();

        if ($request->stage) {
            $query->where('stage', $request->stage);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->session_id) {
            $query->where('session_id', $request->session_id);
        }
        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        $logs = $query->paginate(50)->withQueryString();

        $sessions = ScanLog::select('session_id')
            ->whereNotNull('session_id')
            ->distinct()
            ->latest()
            ->limit(20)
            ->pluck('session_id');

        return view('logs.index', compact('logs', 'sessions'));
    }

    public function clear(Request $request)
    {
        $days = max(1, (int)$request->get('older_than_days', 7));
        $count = ScanLog::where('created_at', '<', now()->subDays($days))->delete();
        return back()->with('success', "Cleared {$count} log entries older than {$days} days.");
    }
}
