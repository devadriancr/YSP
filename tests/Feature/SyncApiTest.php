<?php

use App\Models\SyncRun;
use App\Models\Vendor;
use App\Models\WorkCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const SYNC_TEST_KEY = '0123456789abcdef0123456789abcdef0123456789abcdef';

beforeEach(fn () => config(['portal.sync.key' => SYNC_TEST_KEY]));

/**
 * Sends the body exactly as signed, like supply-relay does.
 */
function signedPost(string $uri, array $payload, ?string $key = SYNC_TEST_KEY, ?int $timestamp = null)
{
    $body = json_encode($payload);
    $timestamp ??= time();

    return test()->call('POST', $uri, server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_SYNC_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_SYNC_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$body, $key),
    ], content: $body);
}

function vendorPayload(array $lines = []): array
{
    return [
        'vendor' => ['name' => 'YOROZU AUTOMOTIVE', 'short_name' => 'YOROZU', 'street' => 'AVENIDA AMISTAD 102', 'state' => 'GTO'],
        'part_numbers' => [
            ['number' => 'BDTS-28-911A', 'description' => 'BRACKET', 'project' => 'J59', 'standard_pack' => 'BOX', 'standard_pack_quantity' => 10, 'side' => 'R', 'next_process' => 'WELDING'],
        ],
        'purchase_order_lines' => $lines ?: [
            ['part_number' => 'BDTS-28-911A', 'number' => 12345, 'line' => 1, 'quantity' => 25, 'due_date' => '2026-10-20', 'shift' => 'A', 'warehouse' => 'YK1'],
            ['part_number' => 'BDTS-28-911A', 'number' => 12345, 'line' => 2, 'quantity' => 10, 'due_date' => '2026-10-21', 'shift' => null, 'warehouse' => 'YK1'],
        ],
        'delivery_rows' => [
            ['part_number' => 'BDTS-28-911A', 'origin' => 'FR', 'number' => null, 'line' => null, 'quantity' => 40, 'due_date' => '2026-10-22'],
        ],
    ];
}

test('a vendor sync replaces all the data of that vendor', function () {
    signedPost('/api/sync/vendors/400191', vendorPayload())->assertOk()->assertJson(['rows' => 4]);

    $vendor = Vendor::firstWhere('number', 400191);
    expect($vendor->name)->toBe('YOROZU AUTOMOTIVE')
        ->and($vendor->synced_at)->not->toBeNull()
        ->and($vendor->purchaseOrderLines)->toHaveCount(2)
        ->and($vendor->deliveryRows)->toHaveCount(1);

    $oneLine = [['part_number' => 'BDTS-28-911A', 'number' => 99, 'line' => 1, 'quantity' => 5, 'due_date' => '2026-11-02']];
    signedPost('/api/sync/vendors/400191', vendorPayload($oneLine))->assertOk();

    expect($vendor->purchaseOrderLines()->pluck('number')->all())->toBe([99])
        ->and($vendor->partNumbers()->count())->toBe(1)
        ->and(SyncRun::where('status', SyncRun::DONE)->count())->toBe(2);
});

test('a line whose part was not sent is rejected and the previous data stays', function () {
    signedPost('/api/sync/vendors/400191', vendorPayload())->assertOk();

    $unknownPart = [['part_number' => 'NOT-SENT', 'number' => 1, 'line' => 1, 'quantity' => 5, 'due_date' => '2026-11-02']];
    signedPost('/api/sync/vendors/400191', vendorPayload($unknownPart))->assertUnprocessable();

    expect(Vendor::firstWhere('number', 400191)->purchaseOrderLines()->count())->toBe(2)
        ->and(SyncRun::latest('id')->first()->status)->toBe(SyncRun::FAILED);
});

test('the work calendar is saved exactly as sent, leading spaces included', function () {
    $days = str_pad('  WW     H', 366, ' ');

    signedPost('/api/sync/work-calendars', ['calendars' => [
        ['facility' => 'YK1', 'work_center' => 0, 'year' => 2026, 'days' => $days],
    ]])->assertOk();

    expect(WorkCalendar::first()->days)->toBe($days);
});

test('requests without a valid signature are rejected', function (Closure $send) {
    $send()->assertUnauthorized();

    expect(Vendor::count())->toBe(0);
})->with([
    'no signature' => fn () => test()->postJson('/api/sync/vendors/400191', vendorPayload()),
    'wrong key' => fn () => signedPost('/api/sync/vendors/400191', vendorPayload(), key: str_repeat('x', 48)),
    'expired' => fn () => signedPost('/api/sync/vendors/400191', vendorPayload(), timestamp: time() - 3600),
]);

test('a body changed after signing is rejected', function () {
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.json_encode(vendorPayload()), SYNC_TEST_KEY);
    $tampered = vendorPayload();
    $tampered['vendor']['name'] = 'SOMEONE ELSE';

    $this->call('POST', '/api/sync/vendors/400191', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_SYNC_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_SYNC_SIGNATURE' => $signature,
    ], content: json_encode($tampered))->assertUnauthorized();
});

test('the same signed request cannot be sent twice', function () {
    $body = json_encode(vendorPayload());
    $timestamp = time();
    $server = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_SYNC_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_SYNC_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$body, SYNC_TEST_KEY),
    ];

    $this->call('POST', '/api/sync/vendors/400191', server: $server, content: $body)->assertOk();
    $this->call('POST', '/api/sync/vendors/400191', server: $server, content: $body)->assertUnauthorized();
});

test('sync is closed while no key is configured', function () {
    config(['portal.sync.key' => '']);

    signedPost('/api/sync/vendors/400191', vendorPayload(), key: '')->assertServiceUnavailable();
});

test('only the allowed ips can send data when a list is configured', function () {
    config(['portal.sync.allowed_ips' => ['10.0.0.0/8']]);

    signedPost('/api/sync/vendors/400191', vendorPayload())->assertForbidden();
});

test('a year made only of working days is accepted', function () {
    signedPost('/api/sync/work-calendars', ['calendars' => [
        ['facility' => 'YK1', 'work_center' => 0, 'year' => 2026, 'days' => str_pad('HHWW', 366, ' ')],
        ['facility' => 'YK1', 'work_center' => 0, 'year' => 2027, 'days' => str_repeat(' ', 366)],
    ]])->assertOk();

    expect(WorkCalendar::count())->toBe(2);
});

test('a vendor with thousands of rows syncs well within the time limit', function () {
    $lines = [];
    for ($i = 0; $i < 8000; $i++) {
        $lines[] = ['part_number' => 'BDTS-28-911A', 'number' => 1000 + intdiv($i, 50), 'line' => $i % 50 + 1, 'quantity' => 25, 'due_date' => '2026-10-20', 'shift' => 'A', 'warehouse' => 'YK1'];
    }

    $started = microtime(true);
    signedPost('/api/sync/vendors/410010', vendorPayload($lines))->assertOk()->assertJson(['rows' => 8002]);

    // The wildcard rules took about a minute with this many lines
    expect(microtime(true) - $started)->toBeLessThan(15);
});

test('a wrong row deep in a list points to its position', function () {
    $lines = vendorPayload()['purchase_order_lines'];
    $lines[1]['due_date'] = '21/10/2026';

    signedPost('/api/sync/vendors/400191', vendorPayload($lines))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['purchase_order_lines.1.due_date']);
});
