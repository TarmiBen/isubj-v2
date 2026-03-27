<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SanctionExpiredReservations;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar job de sanciones cada 15 minutos
Schedule::job(new SanctionExpiredReservations)->everyFifteenMinutes();

