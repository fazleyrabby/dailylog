<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Weekly off-site backup: mirror the local database to Supabase.
// Requires the scheduler to be running (cron: `* * * * * php artisan schedule:run`).
Schedule::command('backup:supabase')->weeklyOn(1, '03:00')->withoutOverlapping();
