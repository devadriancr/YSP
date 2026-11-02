@props([
    // 'labels' | 'di': which document's change to show (day.changes from the calendar summary)
    'kind',
])

{{-- What Infor changed on this day (only today and later): when, and each part from what to what --}}
<template x-if="day.changes?.{{ $kind }}">
    <details class="group mt-2 rounded-lg border border-brand-100 bg-brand-25 dark:border-brand-500/20 dark:bg-brand-500/10">
        <summary class="flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-xs font-medium text-brand-700 marker:hidden dark:text-brand-300">
            <x-svg.refresh class="size-3.5 shrink-0" />
            <span class="min-w-0 flex-1" x-text="changeText(day.changes.{{ $kind }})"></span>
            <x-svg.chevron-down class="size-3.5 shrink-0 transition-transform group-open:rotate-180" />
        </summary>
        {{-- Scrolls on its own: the day modal's list can't scroll (the format menu must open over it) --}}
        <ul class="custom-scrollbar max-h-40 space-y-1 overflow-y-auto overscroll-contain border-t border-brand-100 px-3 py-2 text-xs dark:border-brand-500/20">
            <template x-for="part in day.changes.{{ $kind }}.parts" :key="part.part">
                <li class="flex items-baseline justify-between gap-3">
                    <span class="min-w-0 font-medium break-all text-gray-800 dark:text-white/90" x-text="part.part"></span>
                    <span class="shrink-0 text-end tabular-nums" :class="changeTone(part)" x-text="changePartText(part)"></span>
                </li>
            </template>
        </ul>
    </details>
</template>
