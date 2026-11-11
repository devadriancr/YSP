<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A change Infor made to an unreceived line, found when comparing two syncs.
 */
class UnreceivedChange extends Model
{
    public $timestamps = false;

    protected $fillable = ['vendor_id', 'number', 'line', 'part', 'kind', 'before', 'after', 'detected_at'];

    protected function casts(): array
    {
        return [
            'before' => 'float',
            'after' => 'float',
            'detected_at' => 'datetime',
        ];
    }
}
