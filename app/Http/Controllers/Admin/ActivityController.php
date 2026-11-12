<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersLists;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * What each vendor's users did in the calendar: documents downloaded and new documents opened.
 */
class ActivityController extends Controller
{
    use FiltersLists;

    public const TYPES = ['download' => 'Descarga', 'view' => 'Vio lo nuevo'];

    public function index(Request $request): View
    {
        $vendor = $request->integer('proveedor') ?: null;
        $type = (string) $request->query('tipo');
        $from = $this->date($request, 'desde');
        $to = $this->date($request, 'hasta');

        $downloads = DB::table('document_downloads')
            ->selectRaw("'download' as kind, vendor_id, user_name, user_role, document, date, downloaded_at as at");
        $views = DB::table('document_views as v')
            ->join('document_changes as c', 'c.id', '=', 'v.document_change_id')
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->selectRaw("'view' as kind, c.vendor_id, u.name as user_name, u.role as user_role, c.document, c.date, v.viewed_at as at");

        $activity = DB::query()->fromSub($downloads->unionAll($views), 'a')
            ->when($vendor, fn ($query) => $query->where('vendor_id', $vendor))
            ->when(isset(self::TYPES[$type]), fn ($query) => $query->where('kind', $type))
            ->when($from, fn ($query) => $query->whereDate('at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('at', '<=', $to))
            ->orderByDesc('at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $vendors = Vendor::whereIn('id', $activity->pluck('vendor_id'))->get(['id', 'number', 'name'])->keyBy('id');
        $activity->through(fn (object $row) => (object) [
            ...(array) $row,
            'at' => CarbonImmutable::parse($row->at),
            'date' => CarbonImmutable::parse($row->date),
            'vendor' => $vendors[$row->vendor_id] ?? null,
        ]);

        return view('pages.admin.activity', [
            'title' => 'Actividad de proveedores',
            'activity' => $activity,
            'vendor' => $vendor,
            'type' => $type,
            'types' => self::TYPES,
            'vendors' => $this->vendorOptions(),
            'from' => $from,
            'to' => $to,
            'filtered' => $this->filtered($request, ['proveedor', 'tipo', 'desde', 'hasta']),
        ]);
    }
}
