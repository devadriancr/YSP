@props([
    // 'labels' | 'di': which document's change to show (day.changes from the calendar summary)
    'kind',
])

{{-- Infor changed this day (only today and later): a short note, and the versions to look at or download --}}
<template x-if="day.changes?.{{ $kind }}">
    <details class="group mt-2 rounded-lg border border-brand-100 bg-brand-25 dark:border-brand-500/20 dark:bg-brand-500/10"
        x-on:toggle="$event.target.open && loadVersions('{{ $kind }}')">
        <summary class="flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-xs font-medium text-brand-700 marker:hidden dark:text-brand-300">
            <x-svg.refresh class="size-3.5 shrink-0" />
            <span class="min-w-0 flex-1" x-text="changeText(day.changes.{{ $kind }})"></span>
            <x-svg.chevron-down class="size-3.5 shrink-0 transition-transform group-open:rotate-180" />
        </summary>
        <ul class="space-y-1.5 border-t border-brand-100 px-3 py-2 text-xs dark:border-brand-500/20">
            <template x-for="(version, index) in versions['{{ $kind }}'] || []" :key="version.snapshot">
                <li class="flex items-center justify-between gap-3">
                    <span class="min-w-0 text-gray-800 dark:text-white/90">
                        <span class="font-medium" x-text="versionName(version)"></span>
                        <span class="text-gray-500 dark:text-gray-400" x-text="' · ' + shortDateTime(version.at)"></span>
                        <span x-show="index === (versions['{{ $kind }}'].length - 1)" x-cloak class="ms-1 rounded-full bg-brand-500 px-1.5 py-px text-[10px] font-semibold text-white" x-text="texts.versionCurrent"></span>
                    </span>
                    <span class="flex shrink-0 items-center gap-3">
                        <a :href="versionUrl('{{ $kind }}', version, true)" target="_blank" rel="noopener" class="font-medium text-brand-600 hover:underline dark:text-brand-400" x-text="texts.versionView"></a>
                        <a :href="versionUrl('{{ $kind }}', version, false)" class="font-medium text-brand-600 hover:underline dark:text-brand-400" x-text="texts.versionDownload"></a>
                    </span>
                </li>
            </template>
            <li x-show="versionsLoaded && !(versions['{{ $kind }}'] || []).length" x-cloak class="text-gray-500 dark:text-gray-400" x-text="texts.noVersions"></li>
        </ul>
    </details>
</template>
