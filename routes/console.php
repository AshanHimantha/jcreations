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

// Database backup command
Artisan::command('db:backup', function () {
    $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
    $backupPath = storage_path('app/backups');
    
    if (!file_exists($backupPath)) {
        mkdir($backupPath, 0755, true);
    }
    
    $command = sprintf(
        'mysqldump --user=%s --password=%s %s > %s',
        config('database.connections.mysql.username'),
        config('database.connections.mysql.password'),
        config('database.connections.mysql.database'),
        $backupPath . '/' . $filename
    );
    
    exec($command);
    
    $this->info('Database backup completed successfully!');
})->purpose('Backup the database');

// Schedule database backup to run daily at 1:00 AM
Schedule::command('db:backup')->dailyAt('01:00')->description('Backup database');

