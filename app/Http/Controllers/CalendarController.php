<?php

namespace App\Http\Controllers;

use App\Services\SupplierDocuments;
use App\Support\InforCalendar;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CalendarController extends Controller
{
    // Static copy of the Infor work calendar until the API is available
    private const INFOR_YEAR = 2026;

    private const INFOR_LEAP_YEAR = 0;

    private const INFOR_DAYS = 'HHWW      W      W      W      WH     W      W      W      W     WW     WWH     W      W    H W      W      W      W    H W      W      W      W      W      W      W      W      W     AW      WHHHHHWW      W      W      W      W      W      W      W      W  H   W      W      W      W      W      W      W      W      WH     W      W      W      W      W   HHWW   H ';

    public function index(): View
    {
        return view('pages.calender', [
            'title' => 'Calendario',
            'events' => InforCalendar::toEvents(
                self::INFOR_DAYS,
                self::INFOR_YEAR,
                self::INFOR_LEAP_YEAR === 1,
            ),
        ]);
    }

    /**
     * Days with identification cards and/or delivery instruction in the
     * visible range: { "2026-10-05": { "labels": 12, "di": 30 }, ... }
     */
    public function documents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $from = CarbonImmutable::parse($data['start'])->startOfDay();
        $to = CarbonImmutable::parse($data['end'])->startOfDay()->subDay(); // end is exclusive
        $documents = $this->documentsFor($request);

        try {
            $days = Cache::remember(
                "ysp:documents:v2:{$documents->vendor()}:{$from->format('Ymd')}:{$to->format('Ymd')}",
                now()->addMinutes(config('infor.cache_minutes')),
                fn () => $documents->calendarSummary($from, $to),
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => __('No se pudo consultar Infor.')], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json($days);
    }

    public function downloadLabels(Request $request, string $date): Response
    {
        $day = $this->parseDay($date);
        $documents = $this->documentsFor($request);
        $labels = $documents->labels($day);

        abort_if($labels->isEmpty(), 404, __('No hay etiquetas para este día.'));

        return Pdf::loadView('pdf.identification-cards', ['labels' => $labels])
            ->setPaper('letter')
            ->download("{$day->format('Ymd')}_{$documents->vendor()}_IDENTIFICATION_CARD.pdf");
    }

    public function downloadDeliveryInstruction(Request $request, string $date): Response
    {
        $day = $this->parseDay($date);
        $documents = $this->documentsFor($request);
        $parts = $documents->deliveryInstruction($day);

        abort_if($parts->isEmpty(), 404, __('No hay DI para este día.'));

        return Pdf::loadView('pdf.delivery-instruction', [
            'parts' => $parts,
            'vendor' => $documents->vendor(),
            'vendorName' => $documents->vendorName(),
            'issued' => $day,
        ])
            ->setPaper('letter')
            ->download("{$day->format('Ymd')}_{$documents->vendor()}_DELIVERY_INSTRUCTION.pdf");
    }

    // TODO: use the vendor assigned to the user once the field exists
    private function documentsFor(Request $request): SupplierDocuments
    {
        return new SupplierDocuments(config('infor.default_vendor'));
    }

    private function parseDay(string $date): CarbonImmutable
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date), 404);

        return CarbonImmutable::createFromFormat('!Y-m-d', $date);
    }
}
