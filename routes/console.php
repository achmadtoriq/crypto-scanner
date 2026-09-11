<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Crypto Scanner & Outcome Tracker to run automatically
Schedule::command('scanner:run --interval=15m')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('scanner:run --interval=1h')->hourly()->withoutOverlapping();
Schedule::command('scanner:track')->everyTwoMinutes()->withoutOverlapping();

