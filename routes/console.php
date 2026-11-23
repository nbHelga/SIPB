<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reservation:expire-pending')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('reservasi:send-notifs')
    ->dailyAt('21:46')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
    
Schedule::command('selforder:expire-pending')
    ->everyMinute()
    ->withoutOverlapping();
    //expirependingreservation
