<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersLists;
use App\Http\Controllers\Controller;
use App\Models\PartNumber;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Part numbers of each vendor (IIM + IIU, next process from FRT/LWK). Read only.
 */
class PartNumberController extends Controller
{
    use FiltersLists;

    public function index(Request $request): View
    {
        $search = $this->search($request);
        $vendor = $request->integer('proveedor') ?: null;

        $parts = PartNumber::query()
            ->with('vendor:id,number,name')
            ->when($vendor, fn ($query) => $query->where('vendor_id', $vendor))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('number', "%{$search}%")
                ->orWhereLike('description', "%{$search}%")
                ->orWhereLike('project', "%{$search}%")))
            ->orderBy('number')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.admin.part-numbers', [
            'title' => 'Números de parte',
            'parts' => $parts,
            'search' => $search,
            'vendor' => $vendor,
            'vendors' => $this->vendorOptions(),
            'filtered' => $this->filtered($request, ['buscar', 'proveedor']),
        ]);
    }
}
