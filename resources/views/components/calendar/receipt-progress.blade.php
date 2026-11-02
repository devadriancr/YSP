{{-- Pieces received of the day's purchase orders (HPO), in the labels row of the day modal.
     Green: complete. Red: short on a past day. Amber: on the way. --}}
<div x-show="day.receipt && day.receipt.ordered > 0" class="mt-2">
    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10" role="img" :aria-label="receiptText(day.receipt)">
        <div class="h-full rounded-full transition-[width] motion-reduce:transition-none" :class="receiptTone(day.receipt)"
            :style="`width: ${receiptPercent(day.receipt)}%`"></div>
    </div>
    <p class="mt-1 flex flex-wrap items-center gap-x-1.5 text-xs text-gray-500 dark:text-gray-400">
        <span x-text="receiptText(day.receipt)"></span>
        <template x-if="receiptPending(day.receipt) > 0">
            <span class="font-medium" :class="dayIsOver() ? 'text-error-600 dark:text-error-400' : 'text-warning-600 dark:text-warning-400'"
                x-text="'· ' + count(dayIsOver() ? texts.missed : texts.missing, receiptPending(day.receipt))"></span>
        </template>
        <template x-if="receiptPending(day.receipt) === 0">
            <span class="font-medium text-success-600 dark:text-success-400" x-text="'· ' + texts.complete"></span>
        </template>
    </p>
</div>
