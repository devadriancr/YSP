@php
    $th = 'px-3 py-2.5 text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400';
    $td = 'px-3 py-2.5 text-sm whitespace-nowrap text-gray-700 dark:text-gray-300';
@endphp

{{-- Delivery instruction issued that day: everything due from that day on, by part --}}
<div>
    <div class="relative mb-4">
        <span class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-gray-400"><x-svg.search /></span>
        <label for="di-search" class="sr-only">{{ __('Buscar') }}</label>
        <input id="di-search" type="search" x-model="search" placeholder="{{ __('Buscar por parte, descripción, fecha, origen u orden') }}"
            class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent ps-9 pe-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
    </div>

    <p x-show="filteredParts.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('Nada coincide con la búsqueda.') }}</p>

    <div class="space-y-2">
        <template x-for="part in filteredParts" :key="part.part">
            <details class="group overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800" :open="search.trim() !== '' || filteredParts.length === 1">
                <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3 marker:hidden hover:bg-gray-50 dark:hover:bg-white/3">
                    <svg class="size-4 shrink-0 text-gray-400 transition-transform group-open:rotate-90 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6" /></svg>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-gray-800 dark:text-white/90" x-text="part.part"></span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="part.description"></span>
                    </span>
                    <span class="shrink-0 text-end text-xs text-gray-500 tabular-nums dark:text-gray-400">
                        <span class="block" x-text="count(texts.pieces, sum(part.rows, 'quantity'))"></span>
                        <span class="block font-medium text-gray-700 dark:text-gray-300" x-text="count(texts.boxes, sum(part.rows, 'boxes'))"></span>
                    </span>
                </summary>
                <div class="overflow-x-auto border-t border-gray-100 dark:border-gray-800">
                    <table class="w-full min-w-[480px]">
                        <thead class="bg-gray-50 dark:bg-white/3">
                            <tr>
                                <th class="{{ $th }} text-start">{{ __('Fecha') }}</th>
                                <th class="{{ $th }} text-start">{{ __('Origen') }}</th>
                                <th class="{{ $th }} text-start">{{ __('Orden de compra') }}</th>
                                <th class="{{ $th }} text-end">{{ __('Cantidad') }}</th>
                                <th class="{{ $th }} text-end">{{ __('Cajas') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <template x-for="(row, index) in part.rows" :key="index">
                                <tr>
                                    <td class="{{ $td }}" x-text="shortDate(row.date)"></td>
                                    <td class="px-3 py-2.5">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="{
                                                'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400': row.origin === 'PO',
                                                'bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400': row.origin === 'FP',
                                                'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400': row.origin === 'FR',
                                            }"
                                            x-text="texts.origins[row.origin]"></span>
                                    </td>
                                    <td class="{{ $td }}" x-text="row.order || '—'"></td>
                                    <td class="{{ $td }} text-end tabular-nums" x-text="num(row.quantity)"></td>
                                    <td class="{{ $td }} text-end font-medium tabular-nums" x-text="num(row.boxes)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </details>
        </template>
    </div>
</div>
