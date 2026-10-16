<?php

use App\Http\Controllers\Api\SyncController;
use App\Http\Middleware\VerifySyncSignature;
use Illuminate\Support\Facades\Route;

// Data sent by the sync service (supply-relay), signed with SYNC_KEY
Route::middleware(['throttle:60,1', VerifySyncSignature::class])
    ->prefix('sync')
    ->name('sync.')
    ->group(function () {
        Route::post('/vendors/{number}', [SyncController::class, 'vendor'])
            ->whereNumber('number')
            ->name('vendor');

        Route::post('/work-calendars', [SyncController::class, 'workCalendars'])
            ->name('work-calendars');
    });
