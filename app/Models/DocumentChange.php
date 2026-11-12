<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parts whose quantity Infor changed on a day's identification cards ("labels")
 * or delivery instruction ("di"), found by a sync.
 */
class DocumentChange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'document',
        'date',
        'parts',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'parts' => 'array',
            'detected_at' => 'immutable_datetime',
        ];
    }

    // new: the whole document arrived; removed: all of it is gone; modified: anything else
    public function kind(): string
    {
        return match (true) {
            $this->isNew() => 'new',
            $this->parts !== [] && collect($this->parts)->every(fn (array $part) => $part['after'] === null) => 'removed',
            default => 'modified',
        };
    }

    // The whole document arrived in this sync: every part is new
    public function isNew(): bool
    {
        return $this->parts !== [] && collect($this->parts)->every(fn (array $part) => $part['before'] === null);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
