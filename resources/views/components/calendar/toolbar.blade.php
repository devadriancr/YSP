@props([
    // Vendors of the user and the one being shown
    'vendors' => collect(),
    'vendor' => null,
])

@php
    $vendorIcon = '<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01" /></svg>';
@endphp

{{-- Month on the left, vendor and navigation on the right; calendar-init.js fills the title and drives the buttons --}}
<div data-calendar-toolbar class="flex flex-wrap items-center gap-3 px-4 py-4 sm:px-6">
    <h2 data-calendar-title aria-live="polite"
        class="me-auto text-lg font-semibold text-gray-800 capitalize dark:text-white/90"></h2>

    <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
        @if ($vendors->count() > 1)
            <form method="POST" action="{{ route('calendar.vendor') }}" class="relative min-w-0 flex-1 sm:w-72 sm:flex-none">
                @csrf
                <label for="vendor" class="sr-only">{{ __('Proveedor') }}</label>
                <span class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-gray-400">{!! $vendorIcon !!}</span>
                <select id="vendor" name="vendor" onchange="this.form.submit()"
                    class="shadow-theme-xs h-10 w-full appearance-none truncate rounded-lg border border-gray-300 bg-white ps-9 pe-9 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                    @foreach ($vendors as $option)
                        <option value="{{ $option->number }}" @selected($option->is($vendor))>{{ $option->number }} · {{ $option->name }}</option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.79 7.4 10 12.6l5.21-5.2" /></svg>
                </span>
                <noscript><button type="submit" class="mt-2 text-sm text-brand-600 dark:text-brand-400">{{ __('Cambiar') }}</button></noscript>
            </form>
        @elseif ($vendor)
            <p class="flex h-10 min-w-0 flex-1 items-center gap-2 rounded-lg border border-gray-200 px-3 text-sm text-gray-700 sm:flex-none dark:border-gray-800 dark:text-gray-300">
                <span class="shrink-0 text-gray-400">{!! $vendorIcon !!}</span>
                <span class="truncate">{{ $vendor->number }} · {{ $vendor->name }}</span>
            </p>
        @endif

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
