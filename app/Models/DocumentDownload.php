<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A download of identification cards ("labels") or a delivery instruction ("di")
 * from the calendar. The fingerprint is the one of the data at that moment.
 */
class DocumentDownload extends Model
{
    public const LABELS = 'labels';

    public const DELIVERY_INSTRUCTION = 'di';

    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'user_id',
        'user_name',
        'user_role',
        'document',
        'date',
        'fingerprint',
        'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'downloaded_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
