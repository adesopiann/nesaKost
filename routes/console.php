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
