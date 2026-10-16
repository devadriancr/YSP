<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncRun extends Model
{
    public const RUNNING = 'running';

    public const DONE = 'done';

    public const FAILED = 'failed';

    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'type',
        'status',
        'rows_received',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'rows_received' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
