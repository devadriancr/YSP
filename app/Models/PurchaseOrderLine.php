<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'vendor_id',
        'part_number_id',
        'number',
        'line',
        'quantity',
        'due_date',
        'shift',
        'warehouse',
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
