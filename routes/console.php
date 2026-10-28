<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Needs cron to run `php artisan schedule:run` every minute on the server.
// Run in the scheduler's own process, not started as a command of its own:
// the hosting disables proc_open, which that needs — there it never ran,
// and logged an error every minute instead.
Schedule::call(fn () => Artisan::call('content:publish-due'))
    ->name('content:publish-due')
    ->everyMinute()
    ->withoutOverlapping();
