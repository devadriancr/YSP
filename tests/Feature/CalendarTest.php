<?php

use App\Models\User;
use App\Support\InforCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login redirects to the calendar', function () {
    $user = User::factory()->create();

    $this->post('/signin', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/calendar');
});

test('login from the site root also lands on the calendar', function () {
    $user = User::factory()->create();

    $this->get('/')->assertRedirect('/signin');

    $this->post('/signin', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/calendar');
});

test('login keeps any other intended page', function () {
    $user = User::factory()->create();

    $this->get('/profile')->assertRedirect('/signin');

    $this->post('/signin', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/profile');
});

test('calendar page renders the Infor work calendar', function () {
    $this->actingAs(User::factory()->create())
        ->get('/calendar')
        ->assertOk()
        ->assertSee('No Laboral')
        ->assertSee('2026-09-16', false);
});

test('infor calendar string becomes labels and shaded weekends', function () {
    // Jan 1-2 holidays, Jan 3-4 weekend, Jan 5-10 working days, Jan 11 weekend, Jan 12 half shift
    $days = str_pad('HHWW      WA', 366, ' ');

    $events = InforCalendar::toEvents($days, 2026, false);

    expect($events)->toBe([
        ['start' => '2026-01-01', 'end' => '2026-01-02', 'title' => 'No Laboral', 'type' => 'danger', 'display' => 'block'],
        ['start' => '2026-01-02', 'end' => '2026-01-03', 'title' => 'No Laboral', 'type' => 'danger', 'display' => 'block'],
        ['start' => '2026-01-03', 'end' => '2026-01-05', 'title' => '', 'type' => 'weekend', 'display' => 'background'],
        ['start' => '2026-01-11', 'end' => '2026-01-12', 'title' => '', 'type' => 'weekend', 'display' => 'background'],
        ['start' => '2026-01-12', 'end' => '2026-01-13', 'title' => 'Medio turno', 'type' => 'primary', 'display' => 'block'],
    ]);
});

test('a non-leap year ignores the 366th character', function () {
    $days = str_repeat(' ', 365).'H';

    expect(InforCalendar::toEvents($days, 2026, false))->toBe([]);
});
