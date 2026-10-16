<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersLists;
use App\Http\Controllers\Controller;
use App\Models\WorkCalendar;
use App\Support\InforCalendar;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Work calendars received from Infor (FSC), with how many days of each kind they have. Read only.
 */
class WorkCalendarController extends Controller
{
    use FiltersLists;

    public function index(Request $request): View
    {
        $calendars = WorkCalendar::query()
            ->orderByDesc('year')
            ->orderBy('facility')
            ->orderBy('work_center')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Days of each kind in the year (a non-leap year ignores the 366th character)
        $calendars->getCollection()->transform(function (WorkCalendar $calendar) {
            $days = substr(str_pad($calendar->days, 366), 0, $calendar->isLeapYear() ? 366 : 365);
            $calendar->counts = [
                'working' => substr_count($days, InforCalendar::WORKING_DAY),
                'holiday' => substr_count($days, InforCalendar::HOLIDAY),
                'half' => substr_count($days, InforCalendar::HALF_SHIFT),
                'rest' => substr_count($days, InforCalendar::REST_DAY),
            ];

            return $calendar;
        });

        return view('pages.admin.work-calendars', [
            'title' => 'Calendario laboral',
            'calendars' => $calendars,
            'shown' => [config('portal.calendar.facility'), config('portal.calendar.work_center')],
        ]);
    }
}
