<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sinkronisasi lifecycle berjalan setiap menit agar perubahan status tidak
// bergantung pada tenant membuka halaman aplikasi.
Schedule::command('ekost:sync-rental-lifecycle')
    ->everyMinute()
    ->withoutOverlapping();

// Pengingat masa sewa dijalankan terpisah dari sinkronisasi lifecycle.
// Waktu mengikuti timezone aplikasi (Asia/Jakarta).
Schedule::command('ekost:send-rent-expiration-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Retry email pengingat yang gagal, dengan jeda retry minimal 5 menit.
Schedule::command('ekost:retry-rent-expiration-emails')
    ->everyFiveMinutes()
    ->withoutOverlapping();
