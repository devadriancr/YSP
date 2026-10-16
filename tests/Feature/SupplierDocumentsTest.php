<?php

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
