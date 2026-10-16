<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersLists;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vendors received from Infor (AVM) through Supply Relay. Read only.
 */
class VendorController extends Controller
{
    use FiltersLists;

    public function index(Request $request): View
    {
        $search = $this->search($request);

        $vendors = Vendor::query()
            ->withCount(['users', 'partNumbers', 'purchaseOrderLines', 'deliveryRows'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('short_name', "%{$search}%")
                ->orWhereLike('tax_id', "%{$search}%")
                ->when(ctype_digit($search), fn ($query) => $query->orWhere('number', (int) $search))))
            ->orderBy('number')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.admin.vendors', [
            'title' => 'Proveedores',
            'vendors' => $vendors,
            'search' => $search,
            'filtered' => $this->filtered($request, ['buscar']),
        ]);
    }
}
