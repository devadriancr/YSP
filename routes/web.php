<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;

// Locale Switch Route
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
use App\Http\Controllers\DashboardController;

Route::middleware('auth')->group(function () {
    // dashboard pages
    Route::get('/', function () {
        return view('pages.dashboard.ecommerce', ['title' => 'E-commerce Dashboard']);
    })->name('dashboard');

    // calender pages
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/documents', [CalendarController::class, 'documents'])->name('calendar.documents');
    Route::post('/calendar/vendor', [CalendarController::class, 'selectVendor'])->name('calendar.vendor');
    Route::get('/calendar/{date}/identification-cards', [CalendarController::class, 'downloadLabels'])
        ->name('calendar.labels');
    Route::get('/calendar/{date}/delivery-instruction', [CalendarController::class, 'downloadDeliveryInstruction'])
        ->name('calendar.delivery-instruction');
    Route::get('/calendar/{date}/details', [CalendarController::class, 'details'])->name('calendar.details');

    // users (admins only)
    Route::resource('users', UserController::class)
        ->except('show')
        ->middleware('admin');

    // data received from Infor and the syncs (admins only, read only)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/sync-runs', [Admin\SyncRunController::class, 'index'])->name('sync-runs.index');
        Route::get('/vendors', [Admin\VendorController::class, 'index'])->name('vendors.index');
        Route::get('/part-numbers', [Admin\PartNumberController::class, 'index'])->name('part-numbers.index');
        Route::get('/purchase-order-lines', [Admin\PurchaseOrderLineController::class, 'index'])->name('purchase-order-lines.index');
        Route::get('/delivery-rows', [Admin\DeliveryRowController::class, 'index'])->name('delivery-rows.index');
        Route::get('/work-calendars', [Admin\WorkCalendarController::class, 'index'])->name('work-calendars.index');
    });

    // profile pages
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/browser-sessions', [ProfileController::class, 'logoutOtherBrowserSessions'])
        ->name('profile.browser-sessions.destroy');

    // form pages
    Route::get('/form-elements', function () {
        return view('pages.form.form-elements', ['title' => 'Form Elements']);
    })->name('form-elements');

    // tables pages
    Route::get('/basic-tables', function () {
        return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
    })->name('basic-tables');

    // pages

    Route::get('/blank', function () {
        return view('pages.blank', ['title' => 'Blank']);
    })->name('blank');

    // chart pages
    Route::get('/line-chart', function () {
        return view('pages.chart.line-chart', ['title' => 'Line Chart']);
    })->name('line-chart');

    Route::get('/bar-chart', function () {
        return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
    })->name('bar-chart');

    // ui elements pages
    Route::get('/alerts', function () {
        return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
    })->name('alerts');

    Route::get('/avatars', function () {
        return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
    })->name('avatars');

    Route::get('/badge', function () {
        return view('pages.ui-elements.badges', ['title' => 'Badges']);
    })->name('badges');

    Route::get('/buttons', function () {
        return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
    })->name('buttons');

    Route::get('/image', function () {
        return view('pages.ui-elements.images', ['title' => 'Images']);
    })->name('images');

    Route::get('/videos', function () {
        return view('pages.ui-elements.videos', ['title' => 'Videos']);
    })->name('videos');
});

// error pages
Route::get('/error-404', function () {
    return view('pages.errors.error-404', ['title' => 'Página no encontrada']);
})->name('error-404');


// Any other address: goes through the web middleware (session, language) so the 404 page is in the user's language
Route::fallback(function (Illuminate\Http\Request $request) {
    // API callers (supply-relay) keep getting JSON
    abort_if($request->expectsJson(), 404);

    return response()->view('pages.errors.error-404', ['title' => 'Página no encontrada'], 404);
});
