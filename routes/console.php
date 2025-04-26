<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\MaintenanceController;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Add maintenance cleanup schedule
Schedule::call(function () {
    app(MaintenanceController::class)->cleanupOldData();
})->weekly()->description('Clean up old carts and pending orders');

