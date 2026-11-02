@php
    $th = 'px-3 py-2.5 text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400';
    $td = 'px-3 py-2.5 text-sm whitespace-nowrap text-gray-700 dark:text-gray-300';
@endphp

{{-- One root element: the view is shown with <template x-if>, which only renders the first one --}}
<div>
{{-- Parts still missing on the day (one part may come in several lines), the biggest first --}}
<div x-show="details.shortages?.length" class="mb-4 rounded-xl border p-4"
    :class="dayIsOver() ? 'border-error-200 bg-error-50 dark:border-error-500/30 dark:bg-error-500/10' : 'border-warning-200 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10'">
    <p class="text-sm font-semibold" :class="dayIsOver() ? 'text-error-700 dark:text-error-400' : 'text-warning-700 dark:text-warning-400'"
        x-text="dayIsOver() ? @js(__('Faltó por recibir')) : @js(__('Pendiente por recibir'))"></p>
    <ul class="mt-3 grid gap-2 sm:grid-cols-2">
        <template x-for="part in details.shortages" :key="part.part">
            <li class="rounded-lg bg-white px-3 py-2.5 shadow-theme-xs dark:bg-gray-900">
                <div class="flex items-baseline justify-between gap-3">
                    <span class="min-w-0 truncate text-sm font-medium text-gray-800 dark:text-white/90" x-text="part.part"></span>
                    <span class="shrink-0 text-sm font-semibold tabular-nums"
                        :class="dayIsOver() ? 'text-error-600 dark:text-error-400' : 'text-warning-600 dark:text-warning-400'"
                        x-text="count(texts.missingShort, part.pending)"></span>
                </div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                    <div class="h-full rounded-full bg-success-500" :style="`width: ${receiptPercent({ ordered: part.quantity, received: part.received })}%`"></div>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="receiptText({ ordered: part.quantity, received: part.received })"></p>
            </li>
        </template>
    </ul>
</div>

{{-- Identification cards of the day: one row per purchase order line --}}
<div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
    <table class="w-full min-w-[720px]">
        <thead class="bg-gray-50 dark:bg-white/3">
            <tr>
                <th class="{{ $th }} text-start">{{ __('Número de parte') }}</th>
                <th class="{{ $th }} text-start">{{ __('Orden de compra') }}</th>
                <th class="{{ $th }} text-start">{{ __('Turno') }}</th>
                <th class="{{ $th }} text-start">{{ __('Almacén') }}</th>
                <th class="{{ $th }} text-end">{{ __('Cantidad') }}</th>
                <th class="{{ $th }} text-end">{{ __('Recibido') }}</th>
                <th class="{{ $th }} text-end">{{ __('Pendiente') }}</th>
                <th class="{{ $th }} text-end">{{ __('Cajas') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <template x-for="(line, index) in details.labels" :key="index">
                <tr>
                    <td class="px-3 py-2.5">
                        <p class="text-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90" x-text="line.part"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="line.description"></p>
                    </td>
                    <td class="{{ $td }}" x-text="line.order"></td>
                    <td class="{{ $td }}" x-text="line.shift || '—'"></td>
                    <td class="{{ $td }}" x-text="line.warehouse || '—'"></td>
                    <td class="{{ $td }} text-end tabular-nums" x-text="num(line.quantity)"></td>
                    <td class="{{ $td }} text-end tabular-nums" x-text="num(line.received)"></td>
                    <td class="{{ $td }} text-end tabular-nums">
                        <span x-show="line.pending === 0" class="font-medium text-success-600 dark:text-success-400">✓</span>
                        <span x-show="line.pending > 0" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                            :class="dayIsOver() ? 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400' : 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400'"
                            x-text="num(line.pending)"></span>
                    </td>
                    <td class="{{ $td }} text-end font-medium tabular-nums" x-text="num(line.boxes)"></td>
                </tr>
            </template>
        </tbody>
        <tfoot class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/3">
            <tr>
                <td colspan="4" class="px-3 py-2.5 text-sm font-medium text-gray-800 dark:text-white/90">{{ __('Total') }}</td>
                <td class="{{ $td }} text-end font-medium tabular-nums" x-text="num(sum(details.labels, 'quantity'))"></td>
                <td class="{{ $td }} text-end font-medium tabular-nums" x-text="num(sum(details.labels, 'received'))"></td>
                <td class="{{ $td }} text-end font-semibold tabular-nums" x-text="num(sum(details.labels, 'pending'))"></td>
                <td class="{{ $td }} text-end font-semibold tabular-nums" x-text="num(sum(details.labels, 'boxes'))"></td>
            </tr>
        </tfoot>
    </table>
</div>
</div>
