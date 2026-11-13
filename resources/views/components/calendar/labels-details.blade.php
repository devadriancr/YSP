@php
    $th = 'px-3 py-2.5 text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400';
    $td = 'px-3 py-2.5 text-sm whitespace-nowrap text-gray-700 dark:text-gray-300';
    $search = 'shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent ps-9 pe-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
@endphp

{{-- One root element: the view is shown with <template x-if>, which only renders the first one.
     A single table with every line; the ones still missing are marked in their row. --}}
<div class="grid items-start gap-4">
    <p x-show="details.comparedTo" x-cloak class="mb-3 text-xs text-gray-500 dark:text-gray-400"
        x-text="texts.comparedTo.replace(':date', shortDateTime(details.comparedTo))"></p>

    {{-- Identification cards of the day: one row per purchase order line --}}
    <section aria-labelledby="lines-title" class="min-w-0">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h4 id="lines-title" class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ __('Todas las líneas') }}</h4>
        </div>
        <div class="relative mb-3">
            <span class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-gray-400"><x-svg.search /></span>
            <label for="line-search" class="sr-only">{{ __('Buscar') }}</label>
            <input id="line-search" type="search" x-model="lineSearch" placeholder="{{ __('Buscar por parte, orden, turno o almacén') }}" class="{{ $search }}">
        </div>
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
            <table class="w-full min-w-[640px]">
                <thead class="bg-gray-50 dark:bg-white/3">
                    <tr>
                        <th class="{{ $th }} text-start">{{ __('Número de parte') }}</th>
                        <th class="{{ $th }} text-start">{{ __('Orden de compra') }}</th>
                        <th class="{{ $th }} text-start">{{ __('Turno') }}</th>
                        <th class="{{ $th }} text-start">{{ __('Almacén') }}</th>
                        <th class="{{ $th }} text-end">{{ __('Cantidad') }}</th>
                        <th x-show="!details.version" class="{{ $th }} text-end">{{ __('Recibido') }}</th>
                        <th x-show="!details.version" class="{{ $th }} text-end">{{ __('Pendiente') }}</th>
                        <th class="{{ $th }} text-end">{{ __('Cajas') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-for="(line, index) in filteredLines" :key="index">
                        <tr :class="line.change ? changeTone(line.change) : (!details.version && line.pending > 0 && (dayIsOver() ? 'bg-error-50/60 dark:bg-error-500/10' : 'bg-warning-50/60 dark:bg-warning-500/10'))">
                            <td class="px-3 py-2.5">
                                <p class="text-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90"><span x-text="line.part"></span><span x-show="line.change" x-cloak class="ms-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="changeTag(line.change)" x-text="texts.changeKinds[line.change]"></span></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="line.description"></p>
                            </td>
                            <td class="{{ $td }}" x-text="line.order"></td>
                            <td class="{{ $td }}" x-text="line.shift || '—'"></td>
                            <td class="{{ $td }}" x-text="line.warehouse || '—'"></td>
                            <td class="{{ $td }} text-end tabular-nums">
                                <span x-show="line.change === 'modified' && line.was !== undefined" x-cloak class="me-1 text-gray-400 line-through" x-text="num(line.was)"></span>
                                <span x-text="num(line.quantity)"></span>
                            </td>
                            <td x-show="!details.version" class="{{ $td }} text-end tabular-nums" x-text="num(line.received)"></td>
                            <td x-show="!details.version" class="{{ $td }} text-end tabular-nums">
                                <span x-show="line.pending === 0" class="font-medium text-success-600 dark:text-success-400">✓</span>
                                <span x-show="line.pending > 0" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                    :class="dayIsOver() ? 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400' : 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400'"
                                    x-text="num(line.pending)"></span>
                            </td>
                            <td class="{{ $td }} text-end font-medium tabular-nums" x-text="num(line.boxes)"></td>
                        </tr>
                    </template>
                    <tr x-show="filteredLines.length === 0">
                        <td colspan="8" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('Nada coincide con la búsqueda.') }}</td>
                    </tr>
                </tbody>
                {{-- Totals of the whole day, whatever the search shows --}}
                <tfoot class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/3">
                    <tr>
                        <td colspan="4" class="px-3 py-2.5 text-sm font-medium text-gray-800 dark:text-white/90">{{ __('Total') }}</td>
                        <td class="{{ $td }} text-end font-medium tabular-nums" x-text="num(sum(details.labels, 'quantity'))"></td>
                        <td x-show="!details.version" class="{{ $td }} text-end font-medium tabular-nums" x-text="num(sum(details.labels, 'received'))"></td>
                        <td x-show="!details.version" class="{{ $td }} text-end font-semibold tabular-nums" x-text="num(sum(details.labels, 'pending'))"></td>
                        <td class="{{ $td }} text-end font-semibold tabular-nums" x-text="num(sum(details.labels, 'boxes'))"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</div>
