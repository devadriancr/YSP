@php
    $th = 'px-3 py-2.5 text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400';
    $td = 'px-3 py-2.5 text-sm whitespace-nowrap text-gray-700 dark:text-gray-300';
@endphp

{{-- Identification cards of the day: one row per purchase order line --}}
<div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
    <table class="w-full min-w-[560px]">
        <thead class="bg-gray-50 dark:bg-white/3">
            <tr>
                <th class="{{ $th }} text-start">{{ __('Número de parte') }}</th>
                <th class="{{ $th }} text-start">{{ __('Orden de compra') }}</th>
                <th class="{{ $th }} text-start">{{ __('Turno') }}</th>
                <th class="{{ $th }} text-start">{{ __('Almacén') }}</th>
                <th class="{{ $th }} text-end">{{ __('Cantidad') }}</th>
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
                    <td class="{{ $td }} text-end font-medium tabular-nums" x-text="num(line.boxes)"></td>
                </tr>
            </template>
        </tbody>
        <tfoot class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/3">
            <tr>
                <td colspan="4" class="px-3 py-2.5 text-sm font-medium text-gray-800 dark:text-white/90">{{ __('Total') }}</td>
                <td class="{{ $td }} text-end font-medium tabular-nums" x-text="num(sum(details.labels, 'quantity'))"></td>
                <td class="{{ $td }} text-end font-semibold tabular-nums" x-text="num(sum(details.labels, 'boxes'))"></td>
            </tr>
        </tfoot>
    </table>
</div>
