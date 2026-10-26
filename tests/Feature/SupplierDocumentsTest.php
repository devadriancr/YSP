<?php

use App\Models\DocumentDownload;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Thursday: documents are available from Monday 2026-10-12 on
beforeEach(fn () => $this->travelTo('2026-10-15 09:00'));

/** Vendor with one part and one PO line of 25 pieces in boxes of 10, due on the date. */
function vendorWithLine(int $number, string $dueDate): Vendor
{
    $vendor = Vendor::create(['number' => $number, 'name' => "VENDOR {$number}"]);
    $part = $vendor->partNumbers()->create([
        'number' => 'BDTS28911A',
        'description' => 'BRACKET',
        'project' => 'J59',
        'standard_pack' => 'BOX',
        'standard_pack_quantity' => 10,
        'side' => 'R',
    ]);
    $vendor->purchaseOrderLines()->create([
        'part_number_id' => $part->id,
        'number' => 12345,
        'line' => 1,
        'quantity' => 25,
        'due_date' => $dueDate,
        'shift' => 'A',
        'warehouse' => 'YK1',
    ]);
    $vendor->deliveryRows()->create([
        'part_number_id' => $part->id,
        'origin' => 'FR',
        'quantity' => 40,
        'due_date' => $dueDate,
    ]);

    return $vendor;
}

function supplierOf(Vendor ...$vendors): User
{
    $user = User::factory()->create();
    $user->vendors()->attach(collect($vendors)->pluck('id'));

    return $user;
}

test('the calendar only shows the documents of the vendor of the user', function () {
    $mine = vendorWithLine(400191, '2026-10-20');
    vendorWithLine(200000, '2026-10-21');

    $this->actingAs(supplierOf($mine))
        ->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertOk()
        ->assertExactJson(['2026-10-20' => ['labels' => 3, 'di' => 1]]);
});

test('a user with several vendors switches between them', function () {
    $yorozu = vendorWithLine(400191, '2026-10-20');
    $mazda = vendorWithLine(200000, '2026-10-21');
    $user = supplierOf($yorozu, $mazda);

    // The first vendor by number is shown by default
    $this->actingAs($user)
        ->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertExactJson(['2026-10-21' => ['labels' => 3, 'di' => 1]]);

    $this->actingAs($user)
        ->post('/calendar/vendor', ['vendor' => 400191])
        ->assertRedirect('/calendar');

    $this->actingAs($user)
        ->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertExactJson(['2026-10-20' => ['labels' => 3, 'di' => 1]]);

    $this->actingAs($user)
        ->get('/calendar')
        ->assertSee('400191 · VENDOR 400191')
        ->assertSee('200000 · VENDOR 200000');
});

test('a user cannot choose a vendor that is not assigned to them', function () {
    $mine = vendorWithLine(400191, '2026-10-20');
    vendorWithLine(200000, '2026-10-21');
    $user = supplierOf($mine);

    $this->actingAs($user)
        ->post('/calendar/vendor', ['vendor' => 200000])
        ->assertForbidden();

    $this->actingAs($user)
        ->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertExactJson(['2026-10-20' => ['labels' => 3, 'di' => 1]]);
});

test('a user without vendor cannot see documents', function () {
    vendorWithLine(400191, '2026-10-20');

    $this->actingAs(User::factory()->create())
        ->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get('/calendar/2026-10-20/identification-cards')
        ->assertForbidden();
});

test('identification cards are one per box with the remainder in the last one', function () {
    $vendor = vendorWithLine(400191, '2026-10-20');

    $labels = (new App\Services\SupplierDocuments($vendor))->labels(Carbon\CarbonImmutable::parse('2026-10-20'));

    expect($labels)->toHaveCount(3)
        ->and($labels->pluck('seq')->all())->toBe(['001-003', '002-003', '003-003'])
        ->and($labels->pluck('snp')->all())->toBe([10, 10, 5])
        ->and($labels->first())->toMatchArray([
            'delivery_date' => '20/10-A',
            'order' => '000123450001',
            'part' => 'BDTS-28-911A',
            'rl' => 'R',
            'vendor' => 400191,
            'vendor_name' => 'VENDOR 400191',
        ]);
});

test('identification cards and delivery instruction download as pdf', function () {
    $user = supplierOf(vendorWithLine(400191, '2026-10-20'));

    $this->actingAs($user)
        ->get('/calendar/2026-10-20/identification-cards')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('20261020_400191_IDENTIFICATION_CARD.pdf');

    $this->actingAs($user)
        ->get('/calendar/2026-10-20/delivery-instruction')
        ->assertOk()
        ->assertDownload('20261020_400191_DELIVERY_INSTRUCTION.pdf');

    $this->actingAs($user)
        ->get('/calendar/2026-10-19/identification-cards')
        ->assertNotFound();
});

test('past days show what they had but cannot be downloaded', function () {
    $user = supplierOf(vendorWithLine(400191, '2026-10-09'));

    $this->actingAs($user)
        ->getJson('/calendar/documents?start=2026-09-28&end=2026-11-09')
        ->assertExactJson(['2026-10-09' => ['labels' => 3, 'di' => 1]]);

    $this->actingAs($user)
        ->get('/calendar/2026-10-09/identification-cards')
        ->assertNotFound();

    $this->actingAs($user)
        ->get('/calendar/2026-10-09/delivery-instruction')
        ->assertNotFound();
});

test('the calendar goes back one month at most', function () {
    $august = vendorWithLine(400191, '2026-08-28');
    $september = $august->purchaseOrderLines()->first()->replicate()->fill(['due_date' => '2026-09-01']);
    $september->save();

    $this->actingAs(supplierOf($august))
        ->getJson('/calendar/documents?start=2026-08-01&end=2026-09-05')
        ->assertExactJson(['2026-09-01' => ['labels' => 3, 'di' => 0]]);

    $this->actingAs(supplierOf($august))
        ->get('/calendar')
        ->assertSee('data-visible-from="2026-09-01"', false);
});

test('the current week is available from monday', function () {
    $user = supplierOf(vendorWithLine(400191, '2026-10-12'));

    $this->actingAs($user)
        ->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertExactJson(['2026-10-12' => ['labels' => 3, 'di' => 1]]);

    $this->actingAs($user)
        ->get('/calendar')
        ->assertSee('data-available-from="2026-10-12"', false);
});

// Download flags
test('a download is recorded and the calendar shows who downloaded it and when', function () {
    $vendor = vendorWithLine(400191, '2026-10-20');
    $user = supplierOf($vendor);

    $this->actingAs($user)->get('/calendar/2026-10-20/identification-cards')->assertOk();

    expect(DocumentDownload::sole())
        ->vendor_id->toBe($vendor->id)
        ->user_id->toBe($user->id)
        ->document->toBe('labels');

    $this->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertOk()
        ->assertJsonPath('2026-10-20.labels', 3)
        ->assertJsonPath('2026-10-20.downloads.labels.by', $user->name)
        ->assertJsonPath('2026-10-20.downloads.labels.changed', false)
        ->assertJsonMissingPath('2026-10-20.downloads.di');
});

test('a change in infor after the download marks the labels as changed', function () {
    $vendor = vendorWithLine(400191, '2026-10-20');
    $this->actingAs(supplierOf($vendor))->get('/calendar/2026-10-20/identification-cards');

    // The plant fixes the quantity in Infor and the next sync brings it
    $vendor->purchaseOrderLines()->update(['quantity' => 30]);

    $this->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertJsonPath('2026-10-20.downloads.labels.changed', true);

    // Downloading it again brings it up to date
    $this->get('/calendar/2026-10-20/identification-cards');
    $this->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertJsonPath('2026-10-20.downloads.labels.changed', false);
});

test('the delivery instruction changes when any later row changes', function () {
    $vendor = vendorWithLine(400191, '2026-10-20');
    $user = supplierOf($vendor);
    $this->actingAs($user)->get('/calendar/2026-10-20/delivery-instruction')->assertOk();

    // A forecast for a later day also appears in the DI issued on the 20th
    $vendor->deliveryRows()->create([
        'part_number_id' => $vendor->partNumbers()->value('id'),
        'origin' => 'FR',
        'quantity' => 15,
        'due_date' => '2026-10-28',
    ]);

    $this->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertJsonPath('2026-10-20.downloads.di.changed', true);
});

test('rows removed after the download still shows the download, as changed', function () {
    $vendor = vendorWithLine(400191, '2026-10-20');
    $this->actingAs(supplierOf($vendor))->get('/calendar/2026-10-20/identification-cards');

    $vendor->purchaseOrderLines()->delete();

    $this->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertJsonPath('2026-10-20.labels', 0)
        ->assertJsonPath('2026-10-20.downloads.labels.changed', true);
});

test('downloads of another vendor are not shown', function () {
    $other = vendorWithLine(200000, '2026-10-20');
    $this->actingAs(supplierOf($other))->get('/calendar/2026-10-20/identification-cards');

    $this->actingAs(supplierOf(vendorWithLine(400191, '2026-10-20')))
        ->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertJsonMissingPath('2026-10-20.downloads');
});

test('downloads by planners and admins are kept but not shown, only the supplier ones', function () {
    $vendor = vendorWithLine(400191, '2026-10-20');

    foreach ([User::PLANNER, User::ADMIN] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->withSession(['vendor_id' => $vendor->id])
            ->get('/calendar/2026-10-20/identification-cards')
            ->assertOk();
    }

    expect(DocumentDownload::count())->toBe(2);

    $supplier = supplierOf($vendor);
    $this->actingAs($supplier)->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertJsonMissingPath('2026-10-20.downloads');

    $this->get('/calendar/2026-10-20/identification-cards');

    // The planner sees who of the supplier downloaded it
    $this->actingAs(User::factory()->create(['role' => User::PLANNER]))
        ->withSession(['vendor_id' => $vendor->id])
        ->getJson('/calendar/documents?start=2026-10-01&end=2026-11-01')
        ->assertJsonPath('2026-10-20.downloads.labels.by', $supplier->name);
});

// Details view of the day modal

test('the details of a day list the labels and the delivery instruction without codes', function () {
    $vendor = vendorWithLine(400191, '2026-10-20');
    $vendor->deliveryRows()->create([
        'part_number_id' => $vendor->partNumbers()->value('id'),
        'origin' => 'FP',
        'number' => 777,
        'line' => 2,
        'quantity' => 15,
        'due_date' => '2026-10-27',
    ]);

    $response = $this->actingAs(supplierOf($vendor))
        ->getJson('/calendar/2026-10-20/details')
        ->assertOk()
        ->assertExactJson([
            'labels' => [[
                'part' => 'BDTS-28-911A', 'description' => 'BRACKET', 'order' => '12345-1',
                'shift' => 'A', 'warehouse' => 'YK1', 'quantity' => 25, 'boxes' => 3,
            ]],
            'di' => [[
                'part' => 'BDTS-28-911A', 'description' => 'BRACKET', 'quantity' => 55, 'boxes' => 6,
                'rows' => [
                    ['date' => '2026-10-20', 'origin' => 'FR', 'order' => null, 'quantity' => 40, 'boxes' => 4],
                    ['date' => '2026-10-27', 'origin' => 'FP', 'order' => '777-2', 'quantity' => 15, 'boxes' => 2],
                ],
            ]],
        ]);

    // Nothing that could be printed as a label
    expect($response->getContent())->not->toContain('qr');
});

test('past days can be looked at in detail, but not before the visible month', function () {
    $user = supplierOf(vendorWithLine(400191, '2026-10-09'));

    $this->actingAs($user)->getJson('/calendar/2026-10-09/details')
        ->assertOk()
        ->assertJsonPath('labels.0.order', '12345-1');

    $this->getJson('/calendar/2026-08-31/details')->assertNotFound();
    $this->getJson('/calendar/no-es-fecha/details')->assertNotFound();
});

test('the details only show the vendor of the user', function () {
    vendorWithLine(200000, '2026-10-20');

    $this->actingAs(supplierOf(vendorWithLine(400191, '2026-10-21')))
        ->getJson('/calendar/2026-10-20/details')
        ->assertExactJson(['labels' => [], 'di' => [[
            'part' => 'BDTS-28-911A', 'description' => 'BRACKET', 'quantity' => 40, 'boxes' => 4,
            'rows' => [['date' => '2026-10-21', 'origin' => 'FR', 'order' => null, 'quantity' => 40, 'boxes' => 4]],
        ]]]);
});

test('the calendar page tells the kind of each special day', function () {
    \App\Models\WorkCalendar::create([
        'facility' => config('portal.calendar.facility'),
        'work_center' => config('portal.calendar.work_center'),
        'year' => 2026,
        'days' => str_pad(str_repeat(' ', 292).'HA', 366, ' '), // Oct 20 holiday, Oct 21 half shift
    ]);

    $this->actingAs(supplierOf(vendorWithLine(400191, '2026-10-20')))
        ->get('/calendar')
        ->assertOk()
        ->assertSee('"kind":"holiday"', false)
        ->assertSee('"kind":"half_shift"', false);
});
