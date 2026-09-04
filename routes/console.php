<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup harian (DB + file) - lihat config/backup.php. Baris ini hanya
// terpakai kalau server produksi mendaftarkan `php artisan schedule:run`
// di cron/Task Scheduler; itu langkah setup server yang harus dilakukan user.
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
