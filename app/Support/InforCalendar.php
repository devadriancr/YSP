<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Converts an Infor work calendar into events for the calendar view.
 *
 * Infor stores a year as a string with one character per day, starting on
 * January 1st: ' ' = working day, 'H' = holiday, 'A' = half shift,
 * 'W' = weekend/rest day.
 * The string is 366 characters long; when the year is not a leap year only
 * the first 365 characters are used.
 *
 * Working days produce no event, weekends are shaded and every other day
 * gets its own label ("No Laboral", "Medio turno"; unknown codes show the code).
 * "kind" says what the day is without depending on the language of the title.
 */
final class InforCalendar
{
    public const WORKING_DAY = ' ';

    public const HOLIDAY = 'H';

    public const REST_DAY = 'W';

    public const HALF_SHIFT = 'A';

    /**
     * @return array<int, array{start: string, end: string, title: string, type: string, kind: string, display: string}>
     */
    public static function toEvents(string $days, int $year, bool $isLeapYear): array
    {
        $dayCount = $isLeapYear ? 366 : 365;
        $days = str_pad(substr($days, 0, $dayCount), $dayCount, self::WORKING_DAY);
        $firstDay = CarbonImmutable::create($year, 1, 1);

        $events = [];
        $restStart = null;

        for ($i = 0; $i <= $dayCount; $i++) {
            $code = $days[$i] ?? null;

            // Consecutive rest days become a single shaded range
            if ($code === self::REST_DAY) {
                $restStart ??= $i;

                continue;
            }

            if ($restStart !== null) {
                $events[] = [
                    'start' => $firstDay->addDays($restStart)->toDateString(),
                    'end' => $firstDay->addDays($i)->toDateString(), // exclusive
                    'title' => '',
                    'type' => 'weekend',
                    'kind' => 'rest',
                    'display' => 'background',
                ];
                $restStart = null;
            }

            if ($code === null || $code === self::WORKING_DAY) {
                continue;
            }

            // One label per day, even when several holidays are consecutive
            $events[] = [
                'start' => $firstDay->addDays($i)->toDateString(),
                'end' => $firstDay->addDays($i + 1)->toDateString(),
                ...self::label($code),
                'display' => 'block',
            ];
        }

        return $events;
    }

    /**
     * @return array{title: string, type: string, kind: string}
     */
    private static function label(string $code): array
    {
        return match ($code) {
            self::HOLIDAY => ['title' => __('No Laboral'), 'type' => 'danger', 'kind' => 'holiday'],
            self::HALF_SHIFT => ['title' => __('Medio turno'), 'type' => 'primary', 'kind' => 'half_shift'],
            default => ['title' => $code, 'type' => 'primary', 'kind' => 'other'],
        };
    }
}
