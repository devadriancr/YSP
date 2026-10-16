<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Part of a vendor with the data printed on labels and DI.
 */
class PartNumber extends Model
{
    protected $fillable = [
        'vendor_id',
        'number',
        'description',
        'project',
        'standard_pack',
        'standard_pack_quantity',
        'side',
        'next_process',
    ];

    protected function casts(): array
    {
        return [
            'standard_pack_quantity' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
