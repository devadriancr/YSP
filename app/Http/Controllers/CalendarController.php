<?php

namespace App\Http\Controllers;

use App\Excel\DeliveryInstruction as DeliveryInstructionSheet;
use App\Models\DocumentDownload;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkCalendar;
use App\Pdf\DeliveryInstruction;
use App\Pdf\IdentificationCards;
use App\Services\SupplierDocuments;
use App\Support\InforCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $year = now()->year;

        // This year (the calendar goes back to January) and the next one
        $events = WorkCalendar::query()
            ->where('facility', config('portal.calendar.facility'))
            ->where('work_center', config('portal.calendar.work_center'))
            ->whereBetween('year', [$this->visibleFrom()->year, $year + 1])
            ->orderBy('year')
            ->get()
            ->flatMap(fn (WorkCalendar $calendar) => InforCalendar::toEvents(
                $calendar->days,
                $calendar->year,
                $calendar->isLeapYear(),
            ))
            ->all();

        $vendor = $this->currentVendor($request);

        return view('pages.calender', [
            'title' => 'Calendario',
            'events' => $events,
            'vendors' => $request->user()->visibleVendors(),
            'vendor' => $vendor,
            'availableFrom' => $this->availableFrom()->toDateString(),
            'visibleFrom' => $this->visibleFrom()->toDateString(),
        ]);
    }

    /**
     * Changes the vendor whose documents the calendar shows; only one of the user's vendors.
     */
    public function selectVendor(Request $request): RedirectResponse
    {
        $data = $request->validate(['vendor' => ['required', 'integer']]);

        $vendor = $request->user()->visibleVendors()->firstWhere('number', (int) $data['vendor']);

        abort_if($vendor === null, 403, __('Ese proveedor no está asignado a tu usuario.'));

        $request->session()->put('vendor_id', $vendor->id);

        return redirect()->route('calendar');
    }

    /**
     * Days with identification cards and/or delivery instruction in the visible range,
     * and the last download of each: { "2026-10-05": { "labels": 12, "di": 30,
     * "downloads": { "labels": { "at": "...", "by": "Ana", "changed": false } } }, ... }
     */
    public function documents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        // Past days of this year are shown too (only the last week can be downloaded)
        $from = CarbonImmutable::parse($data['start'])->startOfDay()->max($this->visibleFrom());
        $to = CarbonImmutable::parse($data['end'])->startOfDay()->subDay(); // end is exclusive

        if ($to->lt($from)) {
            return response()->json([]);
        }

        $documents = $this->documentsFor($request);

        return response()->json($this->withDownloads($documents, $documents->calendarSummary($from, $to), $from, $to));
    }

    /**
     * What the identification cards and the delivery instruction of a day contain,
     * for the day modal. Past days too: they can be looked at, not downloaded.
     */
    public function details(Request $request, string $date): JsonResponse
    {
        $day = $this->parseVisibleDay($date);

        return response()->json($this->documentsFor($request)->details($day));
    }

    public function downloadLabels(Request $request, string $date): Response
    {
        $day = $this->parseDay($date);
        $documents = $this->documentsFor($request);
        $labels = $documents->labels($day);

        abort_if($labels->isEmpty(), 404, __('No hay etiquetas para este día.'));

        $pdf = (new IdentificationCards)->render($labels, config('portal.company_name'));
        $this->recordDownload($request, $documents, DocumentDownload::LABELS, $day);

        return $this->pdf($pdf, "{$day->format('Ymd')}_{$documents->vendor()->number}_IDENTIFICATION_CARD.pdf");
    }

    public function downloadDeliveryInstruction(Request $request, string $date): Response
    {
        $day = $this->parseDay($date);
        $documents = $this->documentsFor($request);
        $parts = $documents->deliveryInstruction($day);

        abort_if($parts->isEmpty(), 404, __('No hay instrucción de entrega para este día.'));

        $vendor = $documents->vendor();
        $pdf = (new DeliveryInstruction)->render($parts, $vendor->number, $vendor->name, $day, config('portal.company_name'));
        $this->recordDownload($request, $documents, DocumentDownload::DELIVERY_INSTRUCTION, $day);

        return $this->pdf($pdf, "{$day->format('Ymd')}_{$vendor->number}_DELIVERY_INSTRUCTION.pdf");
    }

    // Same delivery instruction as an Excel sheet; it counts as a download of the DI
    public function downloadDeliveryInstructionExcel(Request $request, string $date): Response
    {
        $day = $this->parseDay($date);
        $documents = $this->documentsFor($request);
        $parts = $documents->deliveryInstructionDetails($day);

        abort_if($parts === [], 404, __('No hay instrucción de entrega para este día.'));

        $vendor = $documents->vendor();
        $xlsx = (new DeliveryInstructionSheet)->render($parts, $vendor->number, $vendor->name, $day, config('portal.company_name'));
        $this->recordDownload($request, $documents, DocumentDownload::DELIVERY_INSTRUCTION, $day);

        return $this->file($xlsx, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', "{$day->format('Ymd')}_{$vendor->number}_DELIVERY_INSTRUCTION.xlsx");
    }

    /**
     * Adds the last download of each document and day by the supplier, and whether Infor
     * changed its data afterwards (the fingerprint then differs from the current one).
     * Downloads by planners and admins are kept but not shown: they are not the supplier's.
     */
    private function withDownloads(SupplierDocuments $documents, array $days, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $latest = $documents->vendor()->documentDownloads()
            ->where('user_role', User::SUPPLIER)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->orderByDesc('downloaded_at')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (DocumentDownload $download) => $download->document.'|'.$download->date->toDateString());

        if ($latest->isEmpty()) {
            return $days;
        }

        $datesOf = fn (string $document) => $latest->where('document', $document)
            ->map(fn (DocumentDownload $download) => $download->date->toDateString())->values()->all();
        $current = [
            DocumentDownload::LABELS => $documents->labelFingerprints($datesOf(DocumentDownload::LABELS)),
            DocumentDownload::DELIVERY_INSTRUCTION => $documents->deliveryInstructionFingerprints($datesOf(DocumentDownload::DELIVERY_INSTRUCTION)),
        ];

        foreach ($latest as $download) {
            $date = $download->date->toDateString();
            // A day whose data was removed after the download still shows that it was downloaded
            $days[$date] ??= ['labels' => 0, 'di' => 0];
            $days[$date]['downloads'][$download->document] = [
                'at' => $download->downloaded_at->timezone(config('app.timezone'))->toIso8601String(),
                'by' => $download->user_name,
                'changed' => $download->fingerprint !== $current[$download->document][$date],
            ];
        }

        ksort($days);

        return $days;
    }

    private function recordDownload(Request $request, SupplierDocuments $documents, string $document, CarbonImmutable $day): void
    {
        $date = $day->toDateString();
        $fingerprint = $document === DocumentDownload::LABELS
            ? $documents->labelFingerprints([$date])[$date]
            : $documents->deliveryInstructionFingerprints([$date])[$date];

        $documents->vendor()->documentDownloads()->create([
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'user_role' => $request->user()->role,
            'document' => $document,
            'date' => $date,
            'fingerprint' => $fingerprint,
            'downloaded_at' => now(),
        ]);
    }

    private function pdf(string $content, string $filename): Response
    {
        return $this->file($content, 'application/pdf', $filename);
    }

    private function file(string $content, string $type, string $filename): Response
    {
        return response($content, 200, [
            'Content-Type' => $type,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // Suppliers only see the documents of their vendors; planners and admins, of any vendor
    private function documentsFor(Request $request): SupplierDocuments
    {
        $vendor = $this->currentVendor($request);

        abort_if($vendor === null, 403, __('Tu usuario no tiene un proveedor asignado.'));

        return new SupplierDocuments($vendor);
    }

    // The vendor chosen in the calendar, always one the user can see (the first one by default)
    private function currentVendor(Request $request): ?Vendor
    {
        $vendors = $request->user()->visibleVendors();

        return $vendors->firstWhere('id', $request->session()->get('vendor_id')) ?? $vendors->first();
    }

    // Downloads: from one week back on
    private function parseDay(string $date): CarbonImmutable
    {
        $day = $this->parseDate($date);

        abort_if($day->lt($this->availableFrom()), 404, __('Esta fecha ya pasó.'));

        return $day;
    }

    // Looking at the details: every day the calendar shows, past ones included
    private function parseVisibleDay(string $date): CarbonImmutable
    {
        $day = $this->parseDate($date);

        abort_if($day->lt($this->visibleFrom()), 404);

        return $day;
    }

    private function parseDate(string $date): CarbonImmutable
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date), 404);

        return CarbonImmutable::createFromFormat('!Y-m-d', $date);
    }

    /**
     * Documents can be downloaded from one week back on (today and the 7 days before);
     * earlier days are shown greyed out with what they had, without downloads.
     */
    private function availableFrom(): CarbonImmutable
    {
        return CarbonImmutable::today()->subWeek();
    }

    /**
     * The calendar goes back to January: every month of this year is visible.
     */
    private function visibleFrom(): CarbonImmutable
    {
        return CarbonImmutable::now()->startOfYear();
    }
}
