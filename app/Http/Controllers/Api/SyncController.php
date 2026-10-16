<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupplierSync;
use App\Support\RowRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives the data the sync service (supply-relay) reads from Infor LX.
 * Requests are authenticated by the VerifySyncSignature middleware.
 */
class SyncController extends Controller
{
    private const PART_NUMBER_RULES = [
        'number' => ['required', 'string', 'max:35'],
        'description' => ['nullable', 'string', 'max:50'],
        'project' => ['nullable', 'string', 'max:255'],
        'standard_pack' => ['nullable', 'string', 'max:255'],
        'standard_pack_quantity' => ['required', 'integer', 'min:0'],
        'side' => ['nullable', 'string', 'max:255'],
        'next_process' => ['nullable', 'string', 'max:30'],
    ];

    private const PURCHASE_ORDER_LINE_RULES = [
        'part_number' => ['required', 'string', 'max:35'],
        'number' => ['required', 'integer', 'min:0'],
        'line' => ['required', 'integer', 'min:0', 'max:65535'],
        'quantity' => ['required', 'numeric'],
        'due_date' => ['required', 'date_format:Y-m-d'],
        'shift' => ['nullable', 'string', 'max:1'],
        'warehouse' => ['nullable', 'string', 'max:3'],
    ];

    private const DELIVERY_ROW_RULES = [
        'part_number' => ['required', 'string', 'max:35'],
        'origin' => ['required', 'string', 'max:2'],
        'number' => ['nullable', 'integer', 'min:0'],
        'line' => ['nullable', 'integer', 'min:0', 'max:65535'],
        'quantity' => ['required', 'numeric'],
        'due_date' => ['required', 'date_format:Y-m-d'],
    ];

    public function vendor(Request $request, int $number, SupplierSync $sync): JsonResponse
    {
        // A big vendor can take longer than a page; supply-relay waits up to 120 s for the answer
        set_time_limit(120);
        $started = microtime(true);

        $data = $request->validate([
            'vendor' => ['required', 'array'],
            'vendor.name' => ['required', 'string', 'max:50'],
            'vendor.short_name' => ['nullable', 'string', 'max:10'],
            'vendor.street' => ['nullable', 'string', 'max:50'],
            'vendor.locality' => ['nullable', 'string', 'max:50'],
            'vendor.region' => ['nullable', 'string', 'max:50'],
            'vendor.state' => ['nullable', 'string', 'max:3'],
            'vendor.postal_code' => ['nullable', 'string', 'max:10'],
            'vendor.country' => ['nullable', 'string', 'max:4'],
            'vendor.tax_id' => ['nullable', 'string', 'max:16'],
            'vendor.phone' => ['nullable', 'string', 'max:25'],
            'vendor.contact_name' => ['nullable', 'string', 'max:30'],
            'vendor.email' => ['nullable', 'string', 'max:50'],
            'part_numbers' => ['present', 'array'],
            'purchase_order_lines' => ['present', 'array'],
            'delivery_rows' => ['present', 'array'],
        ]);

        $data['part_numbers'] = RowRules::validate($data['part_numbers'], 'part_numbers', self::PART_NUMBER_RULES);
        $data['purchase_order_lines'] = RowRules::validate($data['purchase_order_lines'], 'purchase_order_lines', self::PURCHASE_ORDER_LINE_RULES);
        $data['delivery_rows'] = RowRules::validate($data['delivery_rows'], 'delivery_rows', self::DELIVERY_ROW_RULES);
        $validated = microtime(true);

        $run = $sync->vendor($number, $data);

        // Sizes and times, to know where the time goes if a vendor gets slow
        Log::info('Vendor sync', [
            'vendor' => $number,
            'part_numbers' => count($data['part_numbers']),
            'purchase_order_lines' => count($data['purchase_order_lines']),
            'delivery_rows' => count($data['delivery_rows']),
            'validate_s' => round($validated - $started, 2),
            'save_s' => round(microtime(true) - $validated, 2),
        ]);

        return response()->json(['sync_run' => $run->id, 'rows' => $run->rows_received]);
    }

    public function workCalendars(Request $request, SupplierSync $sync): JsonResponse
    {
        $data = $request->validate([
            'calendars' => ['present', 'array'],
            'calendars.*.facility' => ['required', 'string', 'max:3'],
            'calendars.*.work_center' => ['required', 'integer', 'min:0'],
            'calendars.*.year' => ['required', 'integer', 'between:2000,2100'],
            // present, not required: "required" rejects text made only of spaces,
            // and a space is a working day
            'calendars.*.days' => ['present', 'string', 'size:366'],
        ]);

        $run = $sync->workCalendars($data['calendars']);

        return response()->json(['sync_run' => $run->id, 'rows' => $run->rows_received]);
    }
}
