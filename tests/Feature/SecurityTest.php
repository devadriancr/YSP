<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('nobody can sign up: only an admin creates users', function () {
    $this->get('/signup')->assertNotFound();
    $this->post('/signup', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'secret-12345', 'password_confirmation' => 'secret-12345']);

    expect(User::where('email', 'x@example.com')->exists())->toBeFalse();
});

test('pages carry the security headers', function () {
    $response = $this->get('/signin');

    expect($response->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'self'")->toContain("object-src 'none'")
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Permissions-Policy'))->toContain('camera=()');
});

test('the sync api does not answer cross-origin browser requests', function () {
    $response = $this->call('OPTIONS', '/api/sync/work-calendars', server: [
        'HTTP_ORIGIN' => 'https://evil.test',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
    ]);

    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeFalse();
});

test('wrong passwords and rejected sync requests are logged', function () {
    config(['portal.sync.key' => str_repeat('k', 64)]);
    Log::spy();
    User::factory()->create(['email' => 'ana@example.com']);

    $this->post('/signin', ['email' => 'ana@example.com', 'password' => 'wrong-password']);
    $this->postJson('/api/sync/work-calendars', ['calendars' => []])->assertUnauthorized();

    Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context) => $message === 'Failed login' && $context['email'] === 'ana@example.com');
    Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context) => $message === 'Sync request rejected' && $context['reason'] === 'Missing signature.');
});

test('downloads are limited per minute', function () {
    $routes = collect(app('router')->getRoutes())->keyBy(fn ($route) => $route->getName());

    foreach (['calendar.labels', 'calendar.delivery-instruction', 'calendar.delivery-instruction.excel'] as $name) {
        expect($routes[$name]->middleware())->toContain('throttle:30,1');
    }
});
