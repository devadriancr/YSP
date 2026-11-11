@props([
    // Vendors of the user and the one being shown
    'vendors' => collect(),
    'vendor' => null,
])

{{-- Month on the left, vendor and navigation on the right; calendar-init.js fills the title and drives the buttons --}}
{{-- relative z-30: the vendor list opens over the calendar, which paints its own layers --}}
<div data-calendar-toolbar class="relative z-30 flex flex-wrap items-center gap-3 px-4 py-4 sm:px-6">
    <h2 data-calendar-title aria-live="polite"
        class="me-auto text-lg font-semibold text-gray-800 capitalize dark:text-white/90"></h2>

    <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
        <x-common.vendor-picker :vendors="$vendors" :vendor="$vendor" />

        <div class="flex items-center gap-2">
            <button type="button" data-calendar-nav="today"
                class="h-10 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus-visible:ring-3 focus-visible:ring-brand-500/20 focus-visible:outline-none disabled:cursor-default disabled:opacity-40 disabled:hover:bg-transparent dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5">
                {{ __('Hoy') }}
            </button>
            <div class="flex overflow-hidden rounded-lg border border-gray-300 dark:border-gray-700">
                <button type="button" data-calendar-nav="prev" title="{{ __('Mes anterior') }}" aria-label="{{ __('Mes anterior') }}"
                    class="flex size-10 items-center justify-center text-gray-600 transition-colors hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent dark:text-gray-400 dark:hover:bg-white/5">
                    <x-svg.chevron-left />
                </button>
                <button type="button" data-calendar-nav="next" title="{{ __('Mes siguiente') }}" aria-label="{{ __('Mes siguiente') }}"
                    class="flex size-10 items-center justify-center border-s border-gray-300 text-gray-600 transition-colors hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5">
                    <x-svg.chevron-right />
                </button>
            </div>
        </div>
    </div>
</div>
