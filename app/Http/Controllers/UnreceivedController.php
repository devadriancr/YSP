<?php

namespace App\Http\Controllers;

use App\Excel\UnreceivedReport as UnreceivedSheet;
use App\Models\Vendor;
use App\Pdf\UnreceivedReport;
use App\Services\DocumentChanges;
use App\Services\SupplierDocuments;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Overdue parts and raw material the plant has not received, as the Infor reports
 * YPU705B and YPU715B send them to the supplier.
 */
class UnreceivedController extends Controller
{
    // Names of the Infor reports
    private const REPORT_NAMES = [
        'parts' => 'Procurement parts unreceived report',
        'materials' => 'Raw material unreceived report',
    ];

    public function index(Request $request, DocumentChanges $changes): View
    {
        $vendor = $this->vendorOf($request);
        $lines = (new SupplierDocuments($vendor))->unreceived(CarbonImmutable::today());
        $recent = $changes->unreceivedRecent($vendor);

        // What Infor changed in the last days goes on each row; the lines that are gone have no row
        foreach (['parts', 'materials'] as $type) {
            $lines[$type] = array_map(function (array $row) use ($recent) {
                $change = $recent[$row['order']] ?? null;

                return $row + ['change' => $change && $change['kind'] !== 'removed' ? $change : null];
            }, $lines[$type]);
        }
        $shown = collect($lines)->flatten(1)->pluck('order')->all();
        $removed = collect($recent)
            ->filter(fn (array $change, string $order) => $change['kind'] === 'removed' && ! in_array($order, $shown, true))
            ->map(fn (array $change, string $order) => ['order' => $order, ...$change])
            ->sortByDesc('at')
            ->values()
            ->all();

        return view('pages.unreceived', [
            'title' => __('Partes no recibidas'),
            'vendors' => $request->user()->visibleVendors(),
            'vendor' => $vendor,
            'lines' => $lines,
            'removed' => $removed,
        ]);
    }

    /**
     * The report of one tab as PDF or Excel; a vendor without lines gets the "NO DATA" report.
     *
     * @param  'parts'|'materials'  $type
     * @param  'pdf'|'excel'  $format
     */
    public function download(Request $request, string $type, string $format): Response
    {
        $vendor = $this->vendorOf($request);
        $today = CarbonImmutable::today();
        $rows = (new SupplierDocuments($vendor))->unreceived($today)[$type];
        $args = [$type, $rows, $vendor->number, $vendor->name, $today, config('portal.company_name')];

        [$content, $mime, $extension] = $format === 'pdf'
            ? [(new UnreceivedReport)->render(...$args), 'application/pdf', 'pdf']
            : [(new UnreceivedSheet)->render(...$args), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx'];

        $filename = "{$today->format('Ymd')}_{$vendor->number}_".self::REPORT_NAMES[$type].".{$extension}";

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function vendorOf(Request $request): Vendor
    {
        $vendor = $this->currentVendor($request);

        abort_if($vendor === null, 403, __('Tu usuario no tiene un proveedor asignado.'));

        return $vendor;
    }
}
