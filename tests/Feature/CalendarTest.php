<?php

use App\Models\User;
use App\Models\WorkCalendar;
use App\Support\InforCalendar;
use Carbon\CarbonImmutable;
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

/** Calendar string for a year with holidays on the given dates. */
function workDays(string ...$holidays): string
{
    $days = str_repeat(' ', 366);
    foreach ($holidays as $date) {
        $days[CarbonImmutable::parse($date)->dayOfYear - 1] = 'H';
    }

    return $days;
}

test('calendar page renders the work calendar of this year and the next one', function () {
    $this->travelTo('2026-10-15');
    WorkCalendar::create(['facility' => 'YK1', 'work_center' => 0, 'year' => 2026, 'days' => workDays('2026-09-16')]);
    WorkCalendar::create(['facility' => 'YK1', 'work_center' => 0, 'year' => 2027, 'days' => workDays('2027-01-01')]);
    WorkCalendar::create(['facility' => 'YK1', 'work_center' => 0, 'year' => 2025, 'days' => workDays('2025-12-25')]);
    WorkCalendar::create(['facility' => 'YK1', 'work_center' => 99, 'year' => 2026, 'days' => workDays('2026-03-03')]);

    $this->actingAs(User::factory()->create())
        ->get('/calendar')
        ->assertOk()
        ->assertSee('No Laboral')
        ->assertSee('2026-09-16', false)
        ->assertSee('2027-01-01', false)
        ->assertDontSee('2025-12-25', false)
        ->assertDontSee('2026-03-03', false);
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
