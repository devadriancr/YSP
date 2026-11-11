<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Overdue PO line not fully received, as Infor reports it (YPU705B parts / YPU715B raw material).
 */
class UnreceivedLine extends Model
{
    protected $fillable = [
        'vendor_id', 'part_number_id', 'number', 'line', 'item_class', 'quantity', 'received_quantity',
        'due_date', 'material', 'symbol', 'thickness', 'width', 'length',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'line' => 'integer',
            'quantity' => 'decimal:3',
            'received_quantity' => 'decimal:3',
            'due_date' => 'immutable_date',
            'thickness' => 'float',
            'width' => 'float',
            'length' => 'float',
        ];
    }

    // Older lines are not shown or kept: Infor reads back to 2015, the portal one year
    public static function since(): CarbonImmutable
    {
        return CarbonImmutable::today()->subYear();
    }

    // Item class T* is raw material (YPU715B); P* and G* are parts (YPU705B)
    public function isRawMaterial(): bool
    {
        return str_starts_with($this->item_class, 'T');
    }

    // YPU715B: T1/T2 are weighed, T3/T4 counted
    public function unit(): string
    {
        return in_array($this->item_class, ['T1', 'T2'], true) ? 'KG' : 'EA';
    }

    public function partNumber(): BelongsTo
    {
        return $this->belongsTo(PartNumber::class);
    }
}
