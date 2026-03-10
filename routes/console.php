<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule low stock check to run daily at 8:00 AM
Schedule::command('stock:check-low')
    ->dailyAt('08:00')
    ->description('Check for low stock products and send email alerts to admins');
