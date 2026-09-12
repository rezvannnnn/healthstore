<?php

use App\Services\InventoryReservationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inventory:release-expired-reservations', function (InventoryReservationService $service) {
    $releasedCount = $service->releaseExpired();

    $this->info("Released {$releasedCount} expired inventory reservation(s).");
})->purpose('Release expired inventory reservations');
