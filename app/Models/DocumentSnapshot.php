<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * The PO lines and delivery rows of a vendor at one moment (from a week ago on, what can be
 * downloaded), with the part data printed on the documents. Received quantities are not kept.
 */
class DocumentSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = ['vendor_id', 'taken_at', 'payload'];

    protected function casts(): array
    {
        return ['taken_at' => 'immutable_datetime'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @param  Collection<int, PurchaseOrderLine>  $lines  with partNumber
     * @param  Collection<int, DeliveryRow>  $rows  with partNumber
     */
    public static function encode(Collection $lines, Collection $rows): string
    {
        $part = fn ($model) => $model->partNumber->only(['number', 'project', 'description', 'standard_pack', 'standard_pack_quantity', 'side', 'next_process']);

        return base64_encode(gzcompress(json_encode([
            'lines' => $lines->map(fn ($line) => [
                ...$line->only(['number', 'line', 'quantity', 'shift', 'warehouse']),
                'due_date' => $line->due_date->toDateString(),
                'part' => $part($line),
            ])->all(),
            'rows' => $rows->map(fn ($row) => [
                ...$row->only(['origin', 'number', 'line', 'quantity']),
                'due_date' => $row->due_date->toDateString(),
                'part' => $part($row),
            ])->all(),
        ])));
    }

    /**
     * @return Collection<int, PurchaseOrderLine>
     */
    public function lines(): Collection
    {
        return $this->hydrate('lines', PurchaseOrderLine::class);
    }

    /**
     * @return Collection<int, DeliveryRow>
     */
    public function rows(): Collection
    {
        return $this->hydrate('rows', DeliveryRow::class);
    }

    private function hydrate(string $key, string $class): Collection
    {
        $data = json_decode(gzuncompress(base64_decode($this->payload)), true);

        return collect($data[$key])->map(function (array $item) use ($class) {
            $part = (new PartNumber)->forceFill($item['part']);
            unset($item['part']);

            return (new $class)->forceFill([...$item, 'received_quantity' => 0])->setRelation('partNumber', $part);
        });
    }
}
