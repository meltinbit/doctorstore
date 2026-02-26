<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('scans:send-alerts daily')->dailyAt('08:00');
Schedule::command('scans:send-alerts weekly')->weeklyOn(1, '08:00'); // Monday
Schedule::command('scans:run-auto')->everyMinute();
