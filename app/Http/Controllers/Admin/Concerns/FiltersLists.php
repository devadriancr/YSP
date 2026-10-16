<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Vendor;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;

/**
 * Filters shared by the read-only admin lists.
 */
trait FiltersLists
{
    protected const PER_PAGE = 25;

    /**
     * @return array<int, string> vendor id => "400010 · HIRUTA MEXICO SA DE CV"
     */
    protected function vendorOptions(): array
    {
        return Vendor::query()
            ->orderBy('number')
            ->get(['id', 'number', 'name'])
            ->mapWithKeys(fn (Vendor $vendor) => [$vendor->id => "{$vendor->number} · {$vendor->name}"])
            ->all();
    }

    protected function search(Request $request): string
    {
        return trim((string) $request->query('buscar'));
    }

    /**
     * A Y-m-d date from the query string, or null when missing or invalid.
     */
    protected function date(Request $request, string $key): ?string
    {
        $value = (string) $request->query($key);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $value)?->toDateString();
    }

    /**
     * Applies the "desde" and "hasta" filters (inclusive) to a date column.
     */
    protected function betweenDates(Builder $query, Request $request, string $column): void
    {
        if ($from = $this->date($request, 'desde')) {
            $query->whereDate($column, '>=', $from);
        }
        if ($to = $this->date($request, 'hasta')) {
            $query->whereDate($column, '<=', $to);
        }
    }

    /**
     * True when any of the given query string filters has a value (shows "Limpiar").
     */
    protected function filtered(Request $request, array $keys): bool
    {
        return collect($keys)->contains(fn (string $key) => filled($request->query($key)));
    }
}
