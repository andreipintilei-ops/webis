<?php

use Illuminate\Support\Facades\Schedule;

// Needs cron to run `php artisan schedule:run` every minute on the server.
Schedule::command('content:publish-due')->everyMinute()->withoutOverlapping();
