<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Supplier copied from Infor (AVM) by the sync service.
 */
class Vendor extends Model
{
    protected $fillable = [
        'number',
        'name',
        'short_name',
        'street',
        'locality',
        'region',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'phone',
        'contact_name',
        'email',
        'active',
        'synced_at',
        'unreceived_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'active' => 'boolean',
            'synced_at' => 'datetime',
            'unreceived_synced_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function partNumbers(): HasMany
    {
        return $this->hasMany(PartNumber::class);
    }

    public function purchaseOrderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function deliveryRows(): HasMany
    {
        return $this->hasMany(DeliveryRow::class);
    }

    public function unreceivedLines(): HasMany
    {
        return $this->hasMany(UnreceivedLine::class);
    }

    public function unreceivedChanges(): HasMany
    {
        return $this->hasMany(UnreceivedChange::class);
    }

    public function documentDownloads(): HasMany
    {
        return $this->hasMany(DocumentDownload::class);
    }

    public function documentSnapshots(): HasMany
    {
        return $this->hasMany(DocumentSnapshot::class);
    }

    public function documentChanges(): HasMany
    {
        return $this->hasMany(DocumentChange::class);
    }
}
