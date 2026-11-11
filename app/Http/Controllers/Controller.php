<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

abstract class Controller
{
    // The vendor chosen in the vendor picker, always one the user can see (the first one by default)
    protected function currentVendor(Request $request): ?Vendor
    {
        $vendors = $request->user()->visibleVendors();

        return $vendors->firstWhere('id', $request->session()->get('vendor_id')) ?? $vendors->first();
    }
}
