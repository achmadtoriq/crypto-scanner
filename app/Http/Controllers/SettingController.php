<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\TelegramNotifierService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('label')->get()->groupBy('group');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            $dbKey = str_replace('__', '.', $key); // form uses __ for dots
            Setting::set($dbKey, $value);
        }

        return back()->with('success', 'Settings saved successfully!');
    }

    public function testTelegram(TelegramNotifierService $notifier)
    {
        $result = $notifier->testConnection();
        return response()->json($result);
    }
}
