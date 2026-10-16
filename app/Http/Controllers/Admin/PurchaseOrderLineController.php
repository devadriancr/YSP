<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersLists;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrderLine;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Open PO lines (HPO) the identification cards are printed from. Read only.
 */
class PurchaseOrderLineController extends Controller
{
    use FiltersLists;

    public function index(Request $request): View
    {
        $search = $this->search($request);
        $vendor = $request->integer('proveedor') ?: null;

        $lines = PurchaseOrderLine::query()
            ->with(['vendor:id,number,name', 'partNumber'])
            ->when($vendor, fn ($query) => $query->where('vendor_id', $vendor))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereHas('partNumber', fn ($part) => $part->whereLike('number', "%{$search}%"))
                ->when(ctype_digit($search), fn ($query) => $query->orWhere('number', (int) $search))))
            ->tap(fn ($query) => $this->betweenDates($query, $request, 'due_date'))
            ->orderBy('due_date')
            ->orderBy('number')
            ->orderBy('line')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.admin.purchase-order-lines', [
            'title' => 'Etiquetas',
            'lines' => $lines,
            'search' => $search,
            'vendor' => $vendor,
            'vendors' => $this->vendorOptions(),
            'from' => $this->date($request, 'desde'),
            'to' => $this->date($request, 'hasta'),
            'filtered' => $this->filtered($request, ['buscar', 'proveedor', 'desde', 'hasta']),
        ]);
    }
}
