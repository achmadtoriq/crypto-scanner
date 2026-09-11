<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BacktestController;
use App\Http\Controllers\CoinController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ScalpingController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SignalController;
use App\Http\Controllers\TelegramBotController;
use App\Http\Controllers\TrailTestController;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Logout Route (Auth Only)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Application Routes (Requires Login)
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/scanner/run', [DashboardController::class, 'runScanner'])->name('scanner.run');

    // 15M Scalping Command Center
    Route::get('/scalping', [ScalpingController::class, 'index'])->name('scalping.index');

    // Trail Test Engine
    Route::get('/trail-test', [TrailTestController::class, 'index'])->name('trail-test.index');
    Route::get('/trail-test/export', [TrailTestController::class, 'exportCsv'])->name('trail-test.export');

    // Signals
    Route::get('/signals', [SignalController::class, 'index'])->name('signals.index');
    Route::get('/signals/export', [SignalController::class, 'exportCsv'])->name('signals.export');
    Route::post('/signals/track', [SignalController::class, 'trackNow'])->name('signals.track');
    Route::get('/signals/{signal}', [SignalController::class, 'show'])->name('signals.show');
    Route::delete('/signals/{signal}', [SignalController::class, 'destroy'])->name('signals.destroy');
    Route::patch('/signals/{signal}/cancel', [SignalController::class, 'cancel'])->name('signals.cancel');
    Route::patch('/signals/{signal}/reactivate', [SignalController::class, 'reactivate'])->name('signals.reactivate');

    // Coins
    Route::get('/coins', [CoinController::class, 'index'])->name('coins.index');
    Route::get('/coins/{coin}', [CoinController::class, 'show'])->name('coins.show');
    Route::patch('/coins/{coin}/toggle-monitor', [CoinController::class, 'toggleMonitor'])->name('coins.toggle-monitor');

    // Backtest Engine
    Route::get('/backtest', [BacktestController::class, 'index'])->name('backtest.index');
    Route::post('/backtest/run', [BacktestController::class, 'run'])->name('backtest.run');

    // Orders & Executed History Journal
    Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/execute', [\App\Http\Controllers\OrderController::class, 'store'])->name('orders.execute');
    Route::patch('/orders/{order}/status', [\App\Http\Controllers\OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::patch('/orders/{order}/outcome', [\App\Http\Controllers\OrderController::class, 'updateOutcome'])->name('orders.update-outcome');
    Route::delete('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'destroy'])->name('orders.destroy');

    // Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::delete('/logs/clear', [LogController::class, 'clear'])->name('logs.clear');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/settings/test-telegram', [SettingController::class, 'testTelegram'])->name('settings.test-telegram');
});

// Public API & Webhook Endpoints
Route::get('/api/live-price/{symbol}', [CoinController::class, 'getLivePrice'])->name('api.live-price');
Route::post('/api/sync-prices', [CoinController::class, 'syncPrices'])->name('api.sync-prices')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/sync-klines', [CoinController::class, 'syncKlines'])->name('api.sync-klines')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/telegram/webhook', [TelegramBotController::class, 'webhook'])->name('telegram.webhook')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
