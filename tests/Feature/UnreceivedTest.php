<?php

use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Thursday
beforeEach(fn () => $this->travelTo('2026-10-15 09:00'));

/** Vendor with one part and the given unreceived lines. */
function vendorWithUnreceived(int $number, array $lines): Vendor
{
    $vendor = Vendor::create(['number' => $number, 'name' => "VENDOR {$number}"]);
    $part = $vendor->partNumbers()->create(['number' => 'BDTS28911A', 'description' => 'BRACKET', 'project' => 'J59', 'standard_pack_quantity' => 10]);

    foreach ($lines as $i => $line) {
        $vendor->unreceivedLines()->create([
            'part_number_id' => $part->id, 'number' => 500 + $i, 'line' => 1, 'item_class' => 'P1', 'received_quantity' => 0, ...$line,
        ]);
    }

    return $vendor;
}

function unreceivedSupplierOf(Vendor ...$vendors): User
{
    $user = User::factory()->create();
    $user->vendors()->attach(collect($vendors)->pluck('id'));

    return $user;
}

// Infor work calendar of 2026: weekends are rest days and Wednesday 14 October a holiday
function workCalendar2026(): void
{
    $days = '';
    for ($day = CarbonImmutable::create(2026, 1, 1); $day->year === 2026; $day = $day->addDay()) {
        $days .= $day->isWeekend() ? 'W' : ($day->toDateString() === '2026-10-14' ? 'H' : ' ');
    }

    WorkCalendar::create([
        'facility' => config('portal.calendar.facility'),
        'work_center' => config('portal.calendar.work_center'),
        'year' => 2026,
        'days' => str_pad($days, 366),
    ]);
}

test('overdue lines are split in parts and raw material, with the working days late', function () {
    workCalendar2026();
    $vendor = vendorWithUnreceived(400191, [
        ['quantity' => 100, 'received_quantity' => 40, 'due_date' => '2026-10-12'],
        ['quantity' => 1500.5, 'received_quantity' => 500, 'due_date' => '2026-10-01', 'item_class' => 'T1',
            'material' => 'SPCC', 'symbol' => 'SPCC-SD', 'thickness' => 1.6, 'width' => 1219],
        ['quantity' => 10, 'received_quantity' => 10, 'due_date' => '2026-10-05'], // already received
        ['quantity' => 10, 'due_date' => '2026-10-15'], // due today: not late yet
    ]);

    $response = $this->actingAs(unreceivedSupplierOf($vendor))->get('/unreceived')->assertOk()->assertSee('Partes no recibidas');

    $lines = $response->viewData('lines');
    expect($lines['parts'])->toHaveCount(1)
        ->and($lines['parts'][0])->toMatchArray(['part' => 'BDTS28911A', 'order' => '500-1', 'order_no' => '000005000001','pending' => 60.0, 'days_late' => 2])
        ->and($lines['materials'])->toHaveCount(1)
        // Oct 2 to 15 without weekends and the holiday
        ->and($lines['materials'][0])->toMatchArray(['pending' => 1000.5, 'days_late' => 9, 'unit' => 'KG', 'material' => 'SPCC', 'thickness' => 1.6]);
});

test('without the work calendar of a year the days late count monday to friday', function () {
    $vendor = vendorWithUnreceived(400191, [['quantity' => 5, 'due_date' => '2026-10-09']]);

    $lines = $this->actingAs(unreceivedSupplierOf($vendor))->get('/unreceived')->viewData('lines');

    expect($lines['parts'][0]['days_late'])->toBe(4); // Mon 12 to Thu 15
});

test('a supplier only sees the lines of their own vendor', function () {
    $mine = vendorWithUnreceived(400191, [['quantity' => 5, 'due_date' => '2026-10-09']]);
    vendorWithUnreceived(200000, [['quantity' => 7, 'due_date' => '2026-10-09'], ['quantity' => 8, 'due_date' => '2026-10-08']]);

    $lines = $this->actingAs(unreceivedSupplierOf($mine))->get('/unreceived')->viewData('lines');

    expect($lines['parts'])->toHaveCount(1)->and($lines['parts'][0]['pending'])->toBe(5.0);
});

test('choosing another vendor from this page comes back to it', function () {
    $yorozu = vendorWithUnreceived(400191, []);
    $mazda = vendorWithUnreceived(200000, [['quantity' => 7, 'due_date' => '2026-10-09']]);
    $user = unreceivedSupplierOf($yorozu, $mazda);

    $this->actingAs($user)->post('/calendar/vendor', ['vendor' => 400191, 'back' => 'unreceived'])->assertRedirect('/unreceived');
    expect($this->actingAs($user)->get('/unreceived')->viewData('lines')['parts'])->toBe([]);

    // Anything else goes back to the calendar
    $this->actingAs($user)->post('/calendar/vendor', ['vendor' => 200000, 'back' => 'https://evil.example'])->assertRedirect('/calendar');
});

test('each tab downloads as pdf and excel with the name of the infor report', function () {
    $vendor = vendorWithUnreceived(400191, [
        ['quantity' => 100, 'due_date' => '2026-10-12'],
        ['quantity' => 1500.5, 'due_date' => '2026-10-01', 'item_class' => 'T1', 'material' => 'SPCC', 'thickness' => 1.6],
    ]);
    $user = unreceivedSupplierOf($vendor);

    $this->actingAs($user)->get('/unreceived/parts/pdf')->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('20261015_400191_Procurement parts unreceived report.pdf');

    $excel = $this->actingAs($user)->get('/unreceived/materials/excel')->assertOk()
        ->assertDownload('20261015_400191_Raw material unreceived report.xlsx');
    expect(substr($excel->getContent(), 0, 2))->toBe('PK');

    // Nothing pending: still a report, with NO DATA, like Infor
    $this->actingAs(unreceivedSupplierOf(vendorWithUnreceived(200000, [])))
        ->get('/unreceived/materials/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');

    $this->actingAs($user)->get('/unreceived/other/pdf')->assertNotFound();
});

/** What supply-relay sends for a vendor: one part and the unreceived lines given as order => [quantity, received]. */
function syncUnreceived(array $lines, bool $send = true): void
{
    $payload = [
        'vendor' => ['name' => 'VENDOR 400191'],
        'part_numbers' => [['number' => 'BDTS28911A', 'description' => 'BRACKET', 'standard_pack_quantity' => 10]],
        'purchase_order_lines' => [],
        'delivery_rows' => [],
    ];
    if ($send) {
        $payload['unreceived_lines'] = collect($lines)->map(fn ($qty, $order) => [
            'part_number' => 'BDTS28911A', 'number' => $order, 'line' => 1, 'item_class' => 'P1',
            'quantity' => $qty[0], 'received_quantity' => $qty[1], 'due_date' => '2026-10-12',
        ])->values()->all();
    }

    app(App\Services\SupplierSync::class)->vendor(400191, $payload);
}

test('what infor changes in the unreceived lines between two syncs is caught', function () {
    syncUnreceived([100 => [50, 0], 200 => [30, 0], 300 => [10, 0]]);
    $vendor = Vendor::firstWhere('number', 400191);

    // The first load has nothing to compare with
    expect($vendor->unreceivedChanges()->count())->toBe(0);

    // 100: partly received, 200: gone (received or deleted), 400: new; 300 stays the same
    $this->travelTo('2026-10-15 12:00');
    syncUnreceived([100 => [50, 20], 300 => [10, 0], 400 => [5, 0]]);

    expect($vendor->unreceivedChanges()->orderBy('number')->get()->map(fn ($c) => [$c->number, $c->kind, $c->before, $c->after])->all())
        ->toBe([[100, 'changed', 50.0, 30.0], [200, 'removed', 30.0, null], [400, 'new', null, 5.0]]);

    $response = $this->actingAs(unreceivedSupplierOf($vendor))->get('/unreceived')->assertOk();
    $parts = collect($response->viewData('lines')['parts'])->keyBy('order');

    expect($parts['100-1']['change'])->toMatchArray(['kind' => 'changed', 'before' => 50.0, 'after' => 30.0])
        ->and($parts['400-1']['change']['kind'])->toBe('new')
        ->and($parts['300-1']['change'])->toBeNull()
        ->and(array_column($response->viewData('removed'), 'order'))->toBe(['200-1']);
    $response->assertSee('1 línea ya no aparece', false);
});

test('changes older than a week no longer mark the lines', function () {
    syncUnreceived([100 => [50, 0]]);
    $this->travelTo('2026-10-15 12:00');
    syncUnreceived([100 => [50, 20]]);

    $this->travelTo('2026-10-23 09:00');
    $vendor = Vendor::firstWhere('number', 400191);
    $lines = $this->actingAs(unreceivedSupplierOf($vendor))->get('/unreceived')->viewData('lines');

    expect($lines['parts'][0]['change'])->toBeNull();
});

test('nothing is compared the first time an older supply-relay is replaced by a newer one', function () {
    syncUnreceived([], send: false); // older relay: no list
    expect(Vendor::firstWhere('number', 400191)->unreceived_synced_at)->toBeNull();

    syncUnreceived([100 => [50, 0]]); // newer relay: everything would look new
    expect(Vendor::firstWhere('number', 400191)->unreceivedChanges()->count())->toBe(0)
        ->and(Vendor::firstWhere('number', 400191)->unreceived_synced_at)->not->toBeNull();
});

test('only the last year is shown and kept', function () {
    // Today is 2026-10-15: a year back is 2025-10-15
    $mine = vendorWithUnreceived(400191, [
        ['quantity' => 5, 'due_date' => '2025-10-14'], // a day too old
        ['quantity' => 7, 'due_date' => '2025-10-15'],
    ]);
    $other = vendorWithUnreceived(200000, [['quantity' => 9, 'due_date' => '2024-01-10']]);

    $lines = $this->actingAs(unreceivedSupplierOf($mine))->get('/unreceived')->viewData('lines');
    expect(array_column($lines['parts'], 'pending'))->toBe([7.0]);

    // The sync keeps the vendor's last year only and cleans lines of vendors that were not synced
    app(App\Services\SupplierSync::class)->vendor(400191, [
        'vendor' => ['name' => 'VENDOR 400191'],
        'part_numbers' => [['number' => 'BDTS28911A', 'standard_pack_quantity' => 10]],
        'purchase_order_lines' => [],
        'delivery_rows' => [],
        'unreceived_lines' => [
            ['part_number' => 'BDTS28911A', 'number' => 1, 'line' => 1, 'quantity' => 5, 'due_date' => '2015-03-01'],
            ['part_number' => 'BDTS28911A', 'number' => 2, 'line' => 1, 'quantity' => 6, 'due_date' => '2026-01-05'],
        ],
    ]);

    expect(Vendor::firstWhere('number', 400191)->unreceivedLines()->pluck('number')->all())->toBe([2])
        ->and($other->unreceivedLines()->count())->toBe(0);
});

test('the root of the portal opens the calendar, also after logging in', function () {
    $user = unreceivedSupplierOf(vendorWithUnreceived(400191, []));

    $this->actingAs($user)->get('/')->assertRedirect('/calendar');

    // Asking for "/" while logged out must not end on the dashboard after the login
    auth()->logout();
    $this->get('/')->assertRedirect('/signin');
    $user->update(['password' => bcrypt('secret-pass-123')]);
    $this->post('/signin', ['email' => $user->email, 'password' => 'secret-pass-123'])->assertRedirect('/calendar');
});

test('a user without vendor cannot see the page', function () {
    $this->actingAs(User::factory()->create())->get('/unreceived')->assertForbidden();
});
