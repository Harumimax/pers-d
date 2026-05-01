<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('telegram:dispatch-scheduled-sessions')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('telegram:dispatch-interval-review-sessions')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('telegram:cleanup-stale-runs')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('telegram:cleanup-stale-interval-review-runs')
    ->hourly()
    ->withoutOverlapping();
