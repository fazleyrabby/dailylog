<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily off-site backup: mirror the local database to Supabase every night at midnight (UTC).
// Requires the scheduler to be running (cron: `* * * * * php artisan schedule:run`).
Schedule::command('backup:supabase')->dailyAt('00:00')->withoutOverlapping();
