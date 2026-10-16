<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Row of the delivery instruction. Origin is the PO record id (HPO.PID),
 * 'FP' for firm planned orders or 'FR' for forecasts; only PO rows have number and line.
 */
class DeliveryRow extends Model
{
    protected $fillable = [
        'vendor_id',
        'part_number_id',
        'origin',
        'number',
        'line',
        'quantity',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'line' => 'integer',
            'quantity' => 'decimal:3',
            'due_date' => 'immutable_date',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class);
    }
}
