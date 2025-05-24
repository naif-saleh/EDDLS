<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



Schedule::command('dialer:make-calls')->everySecond();

Schedule::command('calls:dialer-update-statuses')->everySecond();

Schedule::command('app:distributor-make-call-command')->everySecond();

Schedule::command('app:distributor-update-call-status')->everySecond();


// Schedule::command('campaign:update-status')->everySecond();
