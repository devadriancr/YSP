@props([
    // 'labels' | 'di': key of the day counts and of the downloads (calendar-documents.js)
    'kind',
    'title',
    // Text when the day has nothing, for upcoming and for past days
    'empty',
    'emptyPast',
    'tone' => 'success',
    // The delivery instruction also downloads as Excel
    'excel' => false,
])

@php
    $tones = [
        'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400',
        'blue-light' => 'bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400',
    ];
    $tile = $tones[$tone] ?? $tones['success'];
@endphp

<li class="flex items-center gap-4 px-5 py-4 sm:px-6">
    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl"
        :class="day.{{ $kind }} > 0 ? '{{ $tile }}' : 'bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500'">
        {{ $icon }}
    </span>

    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $title }}</p>
        <p x-show="day.{{ $kind }} === 0" class="text-sm text-gray-500 dark:text-gray-400"
            x-text="day.past ? @js($emptyPast) : @js($empty)"></p>
        <template x-if="day.downloads?.{{ $kind }}">
            <p class="mt-0.5 text-xs"
                :class="day.downloads.{{ $kind }}.changed ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400'"
                x-text="downloadText(day.downloads.{{ $kind }})"></p>
        </template>
    </div>

    <div x-show="day.{{ $kind }} > 0" class="flex shrink-0 items-center gap-2">
        {{-- Outline buttons: blue to look at the details, grey to download --}}
        <x-ui.icon-button :label="__('Ver detalle')" variant="brand-outline" x-on:click="showDetails('{{ $kind }}')">
            <x-svg.eye />
        </x-ui.icon-button>

        <template x-if="!day.past">
            @if ($excel)
                {{-- Two formats: the download button opens a menu to choose PDF or Excel --}}
                <div x-data="{ formats: false }" class="relative"
                    @click.outside="formats = false" @keydown.escape.stop="formats = false">
                    <x-ui.icon-button :label="__('Descargar')" aria-haspopup="menu"
                        x-on:click="formats = !formats" x-bind:aria-expanded="formats"
                        x-bind:disabled="isPending('{{ $kind }}') || isPending('{{ $kind }}-excel')">
                        <x-svg.download x-show="!isPending('{{ $kind }}') && !isPending('{{ $kind }}-excel')" />
                        <x-svg.spinner x-show="isPending('{{ $kind }}') || isPending('{{ $kind }}-excel')" x-cloak />
                    </x-ui.icon-button>

                    <div x-show="formats" x-cloak role="menu"
                        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="absolute end-0 bottom-full z-10 mb-2 w-40 origin-bottom-right rounded-xl border border-gray-200 bg-white p-1.5 shadow-theme-lg rtl:origin-bottom-left dark:border-gray-800 dark:bg-gray-900">
                        <button type="button" role="menuitem" @click="formats = false; download('{{ $kind }}')"
                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 focus-visible:bg-gray-100 focus-visible:outline-none dark:text-gray-300 dark:hover:bg-white/5 dark:focus-visible:bg-white/5">
                            <x-svg.file-pdf class="size-4 text-error-500" /> PDF
                        </button>
                        <button type="button" role="menuitem" @click="formats = false; download('{{ $kind }}-excel')"
                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 focus-visible:bg-gray-100 focus-visible:outline-none dark:text-gray-300 dark:hover:bg-white/5 dark:focus-visible:bg-white/5">
                            <x-svg.file-excel class="size-4 text-success-600 dark:text-success-500" /> Excel
                        </button>
                    </div>
                </div>
            @else
                <x-ui.icon-button :label="__('Descargar PDF')"
                    x-on:click="download('{{ $kind }}')" x-bind:disabled="isPending('{{ $kind }}')">
                    <x-svg.download x-show="!isPending('{{ $kind }}')" />
                    <x-svg.spinner x-show="isPending('{{ $kind }}')" x-cloak />
                </x-ui.icon-button>
            @endif
        </template>
    </div>
</li>
