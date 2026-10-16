<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersLists;
use App\Http\Controllers\Controller;
use App\Models\SyncRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every request received from Supply Relay: what arrived, when and whether it was saved.
 */
class SyncRunController extends Controller
{
    use FiltersLists;

    public const STATUSES = [SyncRun::DONE => 'Correcta', SyncRun::FAILED => 'Con error', SyncRun::RUNNING => 'En curso'];

    public const TYPES = ['vendor' => 'Proveedor', 'work_calendars' => 'Calendario laboral'];

    public function index(Request $request): View
    {
        $vendor = $request->integer('proveedor') ?: null;
        $status = (string) $request->query('estado');
        $type = (string) $request->query('tipo');

        $runs = SyncRun::query()
            ->with('vendor:id,number,name')
            ->when($vendor, fn ($query) => $query->where('vendor_id', $vendor))
            ->when(isset(self::STATUSES[$status]), fn ($query) => $query->where('status', $status))
            ->when(isset(self::TYPES[$type]), fn ($query) => $query->where('type', $type))
            ->tap(fn ($query) => $this->betweenDates($query, $request, 'started_at'))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $last24h = SyncRun::query()->where('started_at', '>=', now()->subDay());

        return view('pages.admin.sync-runs', [
            'title' => 'Sincronizaciones',
            'runs' => $runs,
            'vendor' => $vendor,
            'status' => $status,
            'type' => $type,
            'vendors' => $this->vendorOptions(),
            'statuses' => self::STATUSES,
            'types' => self::TYPES,
            'from' => $this->date($request, 'desde'),
            'to' => $this->date($request, 'hasta'),
            'filtered' => $this->filtered($request, ['proveedor', 'estado', 'tipo', 'desde', 'hasta']),
            'summary' => [
                'last' => SyncRun::query()->latest('id')->first(),
                'done' => (clone $last24h)->where('status', SyncRun::DONE)->count(),
                'failed' => (clone $last24h)->where('status', SyncRun::FAILED)->count(),
                'rows' => (clone $last24h)->where('status', SyncRun::DONE)->sum('rows_received'),
            ],
        ]);
    }
}
