<?php

use App\Models\SyncRun;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->role = $role;
    $user->save();

    return $user;
}

/** Vendor with one part, one PO line and one forecast row. */
function vendorWithData(int $number, string $name): Vendor
{
    $vendor = Vendor::create(['number' => $number, 'name' => $name, 'tax_id' => "RFC{$number}"]);
    $part = $vendor->partNumbers()->create(['number' => "PART{$number}", 'description' => 'BRACKET', 'standard_pack_quantity' => 10]);
    $vendor->purchaseOrderLines()->create(['part_number_id' => $part->id, 'number' => 55555, 'line' => 1, 'quantity' => 25, 'due_date' => '2026-10-20']);
    $vendor->deliveryRows()->create(['part_number_id' => $part->id, 'origin' => 'FR', 'quantity' => 40, 'due_date' => '2026-10-22']);

    return $vendor;
}

$pages = ['/admin/sync-runs', '/admin/vendors', '/admin/part-numbers', '/admin/purchase-order-lines', '/admin/delivery-rows', '/admin/work-calendars'];

test('the data pages are only for admins', function (string $page) {
    $this->actingAs(userWithRole(User::SUPPLIER))->get($page)->assertForbidden();
    $this->actingAs(userWithRole(User::PLANNER))->get($page)->assertForbidden();
    $this->actingAs(userWithRole(User::ADMIN))->get($page)->assertOk();
})->with($pages);

test('the admin menu group is only shown to admins', function () {
    $this->actingAs(userWithRole(User::ADMIN))->get('/calendar')
        ->assertSee('Administración')
        ->assertSee('/admin/sync-runs', false);

    $this->actingAs(userWithRole(User::PLANNER))->get('/calendar')
        ->assertDontSee('Administración')
        ->assertDontSee('/admin/sync-runs', false);
});

test('the lists show the synced data and filter it by vendor', function () {
    $hiruta = vendorWithData(400010, 'HIRUTA MEXICO');
    vendorWithData(400110, 'SK TEC MEXICANA');
    $admin = userWithRole(User::ADMIN);

    $this->actingAs($admin)->get('/admin/vendors?buscar=hiruta')
        ->assertSee('HIRUTA MEXICO')->assertSee('RFC400010')->assertDontSee('SK TEC MEXICANA');

    $this->actingAs($admin)->get("/admin/part-numbers?proveedor={$hiruta->id}")
        ->assertSee('PART400010')->assertDontSee('PART400110');

    $this->actingAs($admin)->get('/admin/purchase-order-lines?buscar=PART400110')
        ->assertSee('55555 - 1')->assertDontSee('PART400010');

    $this->actingAs($admin)->get('/admin/purchase-order-lines?desde=2026-11-01')
        ->assertSee('Ningún registro coincide con los filtros.');

    $this->actingAs($admin)->get('/admin/delivery-rows?origen=FR')
        ->assertSee('Forecast')->assertSee('PART-40-0010')->assertSee('PART-40-0110'); // printed format

    $this->actingAs($admin)->get('/admin/delivery-rows?origen=PO')
        ->assertSee('Ningún registro coincide con los filtros.');
});

test('the work calendars list counts each kind of day', function () {
    WorkCalendar::create(['facility' => 'YK1', 'work_center' => 0, 'year' => 2026, 'days' => str_pad('HHWWA', 366, ' ')]);

    $this->actingAs(userWithRole(User::ADMIN))->get('/admin/work-calendars')
        ->assertOk()
        ->assertSee('En el portal')
        ->assertSeeInOrder(['2026', 'YK1', '360', '2', '1', '2']);
});

test('the sync runs show their result and error, and can be filtered', function () {
    $vendor = vendorWithData(400010, 'HIRUTA MEXICO');
    SyncRun::create(['type' => 'vendor', 'vendor_id' => $vendor->id, 'status' => SyncRun::DONE, 'rows_received' => 1990, 'started_at' => now(), 'finished_at' => now()]);
    SyncRun::create(['type' => 'work_calendars', 'status' => SyncRun::FAILED, 'rows_received' => 2, 'error' => 'The calendars.1.days field is required.', 'started_at' => now(), 'finished_at' => now()]);
    $admin = userWithRole(User::ADMIN);

    $this->actingAs($admin)->get('/admin/sync-runs')
        ->assertSee('1,990')
        ->assertSee('Con error')
        ->assertSee('The calendars.1.days field is required.');

    $this->actingAs($admin)->get('/admin/sync-runs?estado=failed')
        ->assertSee('Calendario laboral')
        ->assertDontSee('400010 · HIRUTA MEXICO</td>', false);
});

test('a planner sees the calendar of every vendor', function () {
    $hiruta = Vendor::create(['number' => 400010, 'name' => 'HIRUTA']);
    Vendor::create(['number' => 400110, 'name' => 'SK TEC']);
    $planner = userWithRole(User::PLANNER);

    $this->actingAs($planner)->get('/calendar')
        ->assertSee('400010 · HIRUTA')
        ->assertSee('400110 · SK TEC');

    $this->actingAs($planner)->post('/calendar/vendor', ['vendor' => 400110])->assertRedirect('/calendar');
    $this->actingAs($planner)->get('/users')->assertForbidden();

    expect($hiruta->users()->count())->toBe(0); // nothing to assign
});

test('planners and admins are saved without assigned vendors', function () {
    $vendor = Vendor::create(['number' => 400010, 'name' => 'HIRUTA']);

    $this->actingAs(userWithRole(User::ADMIN))->post('/users', [
        'name' => 'Pedro Planeador',
        'email' => 'pedro@ykm.test',
        'role' => 'planner',
        'vendors' => [$vendor->id],
        'password' => 'Secreta-2026',
        'password_confirmation' => 'Secreta-2026',
    ])->assertRedirect('/users');

    $planner = User::firstWhere('email', 'pedro@ykm.test');
    expect($planner->role)->toBe('planner')
        ->and($planner->vendors)->toBeEmpty()
        ->and($planner->visibleVendors()->pluck('number')->all())->toBe([400010]);
});
