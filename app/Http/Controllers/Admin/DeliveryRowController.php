<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersLists;
use App\Http\Controllers\Controller;
use App\Models\DeliveryRow;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Delivery instruction rows: PO lines, firm planned orders and forecasts. Read only.
 */
class DeliveryRowController extends Controller
{
    use FiltersLists;

    // Origins that are not a purchase order record (HPO.PID)
    public const ORIGINS = ['FP' => 'Planeada en firme', 'FR' => 'Forecast de cliente'];

    public function index(Request $request): View
    {
        $search = $this->search($request);
        $vendor = $request->integer('proveedor') ?: null;
        $origin = (string) $request->query('origen');

        $rows = DeliveryRow::query()
            ->with(['vendor:id,number,name', 'partNumber'])
            ->when($vendor, fn ($query) => $query->where('vendor_id', $vendor))
            ->when($origin === 'PO', fn ($query) => $query->whereNotIn('origin', array_keys(self::ORIGINS)))
            ->when(isset(self::ORIGINS[$origin]), fn ($query) => $query->where('origin', $origin))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereHas('partNumber', fn ($part) => $part->whereLike('number', "%{$search}%"))
                ->when(ctype_digit($search), fn ($query) => $query->orWhere('number', (int) $search))))
            ->tap(fn ($query) => $this->betweenDates($query, $request, 'due_date'))
            ->orderBy('due_date')
            ->orderBy('part_number_id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.admin.delivery-rows', [
            'title' => 'Instrucción de entrega',
            'rows' => $rows,
            'search' => $search,
            'vendor' => $vendor,
            'origin' => $origin,
            'origins' => ['PO' => 'Orden de compra', ...self::ORIGINS],
            'vendors' => $this->vendorOptions(),
            'from' => $this->date($request, 'desde'),
            'to' => $this->date($request, 'hasta'),
            'filtered' => $this->filtered($request, ['buscar', 'proveedor', 'origen', 'desde', 'hasta']),
        ]);
    }
}
