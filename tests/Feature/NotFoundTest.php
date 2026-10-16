<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unknown address shows the not found page with a link to the calendar', function () {
    $this->actingAs(User::factory()->create())
        ->get('/no-existe')
        ->assertNotFound()
        ->assertSee('Página no encontrada')
        ->assertSee(route('calendar'));
});

test('the not found page follows the language', function () {
    $this->actingAs(User::factory()->create());
    $this->get('/locale/ja');

    $this->get('/no-existe')->assertNotFound()->assertSee('ページが見つかりません')->assertSee('カレンダーへ');
});

test('a missing record shows the same page', function () {
    $admin = User::factory()->create(['role' => User::ADMIN]);

    $this->actingAs($admin)->get('/users/999999/edit')->assertNotFound()->assertSee('Página no encontrada');
});

test('api callers still get json', function () {
    $this->getJson('/api/no-existe')->assertNotFound()->assertHeader('content-type', 'application/json')->assertDontSee('<html', false);
});
