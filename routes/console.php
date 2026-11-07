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
    ->dailyAt('05:20')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
    
Schedule::command('selforder:expire-pending')
    ->everyMinute()
    ->withoutOverlapping();
    //expirependingreservation
