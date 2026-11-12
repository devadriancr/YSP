@props([
    // 'labels' | 'di': which document's change to show (day.changes from the calendar summary)
    'kind',
])

{{-- Infor changed this day (only today and later): a short note, and the versions to look at or download --}}
<template x-if="day.changes?.{{ $kind }}">
    <details class="group mt-2 rounded-lg border border-brand-100 bg-brand-25 dark:border-brand-500/20 dark:bg-brand-500/10"
        x-on:toggle="$event.target.open && loadVersions('{{ $kind }}')">
        <summary class="flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-xs font-medium text-brand-700 marker:hidden dark:text-brand-300">
            <x-calendar.change-icon of="day.changes.{{ $kind }}.kind" />
            <span class="min-w-0 flex-1" x-text="changeText(day.changes.{{ $kind }})"></span>
            <x-svg.chevron-down class="size-3.5 shrink-0 transition-transform group-open:rotate-180" />
        </summary>
        <ul class="space-y-1.5 border-t border-brand-100 px-3 py-2 text-xs dark:border-brand-500/20">
            <template x-for="(version, index) in versions['{{ $kind }}'] || []" :key="version.snapshot">
                <li class="flex items-center justify-between gap-3">
                    <span class="min-w-0 text-gray-800 dark:text-white/90">
                        {{-- Icon and date of the load; only the first one, which has no change, says "Original" --}}
                        <span class="inline-flex items-center gap-1.5 align-middle" :title="versionName(version)">
                            <span x-show="version.original" x-text="texts.versionOriginal" class="font-medium"></span>
                            <x-calendar.change-icon of="version.kind" x-show="!version.original" class="text-brand-600 dark:text-brand-400" />
                            <span class="text-gray-600 dark:text-gray-300" x-text="shortDateTime(version.at)"></span>
                        </span>
                        <span x-show="index === (versions['{{ $kind }}'].length - 1)" x-cloak class="ms-1 rounded-full bg-brand-500 px-1.5 py-px text-[10px] font-semibold text-white" x-text="texts.versionCurrent"></span>
                    </span>
                    <div class="flex shrink-0 items-center gap-2">
                        {{-- View: the document of that version, in this same window --}}
                        <x-ui.icon-button :label="__('Ver')" size="sm" variant="brand-outline" x-on:click="showDetails('{{ $kind }}', version)">
                            <x-svg.eye />
                        </x-ui.icon-button>

@if ($kind === 'di')
                        {{-- Download: the menu chooses PDF or Excel --}}
                        <div x-data="{ menu: false }" class="relative" @click.outside="menu = false" @keydown.escape.stop="menu = false">
                            <x-ui.icon-button :label="__('Descargar')" size="sm" aria-haspopup="menu"
                                x-on:click="menu = !menu" x-bind:aria-expanded="menu">
                                <x-svg.download />
                            </x-ui.icon-button>
                            <div x-show="menu" x-cloak role="menu"
                                class="absolute end-0 top-full z-10 mt-1 w-36 rounded-xl border border-gray-200 bg-white p-1.5 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                                <a role="menuitem" :href="versionUrl('{{ $kind }}', version, 'pdf')" @click="menu = false"
                                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                                    <x-svg.file-pdf class="size-4 text-error-500" /> PDF
                                </a>
                                <a role="menuitem" :href="versionUrl('{{ $kind }}', version, 'excel')" @click="menu = false"
                                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                                    <x-svg.file-excel class="size-4 text-success-600 dark:text-success-500" /> Excel
                                </a>
                            </div>
                        </div>
@else
                        {{-- Download: the labels only have the PDF --}}
                        <a :href="versionUrl('{{ $kind }}', version, 'pdf')" :title="texts.versionDownload" :aria-label="texts.versionDownload"
                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-800 focus-visible:ring-3 focus-visible:ring-brand-500/20 focus-visible:outline-none dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white/90">
                            <x-svg.download />
                        </a>
@endif
                    </div>
                </li>
            </template>
            <li x-show="versionsLoaded && !(versions['{{ $kind }}'] || []).length" x-cloak class="text-gray-500 dark:text-gray-400" x-text="texts.noVersions"></li>
        </ul>
    </details>
</template>
