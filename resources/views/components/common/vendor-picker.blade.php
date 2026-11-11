@props([
    // Vendors of the user and the one being shown
    'vendors' => collect(),
    'vendor' => null,
    // Route to come back to after choosing: calendar or unreceived
    'back' => 'calendar',
])

@php
    $vendorIcon = '<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01" /></svg>';
    $options = $vendors->map(fn ($option) => ['number' => $option->number, 'name' => $option->name])->values();
@endphp

{{-- Vendor picker (TailAdmin dropdown): picking one submits the form; one vendor is shown as plain text --}}
@if ($vendors->count() > 1)
    <form method="POST" action="{{ route('calendar.vendor') }}" class="relative min-w-0 flex-1 sm:w-80 sm:flex-none"
        x-data="{
            open: false,
            query: '',
            options: @js($options),
            current: @js($vendor?->number),
            matches(number, name) {
                const q = this.query.trim().toLowerCase();
                return !q || String(number).startsWith(q) || name.toLowerCase().includes(q);
            },
            get filtered() {
                return this.options.filter(o => this.matches(o.number, o.name));
            },
            get selected() {
                return this.options.find(o => o.number === this.current) || this.options[0];
            },
            choose(number) {
                this.open = false;
                if (number === this.current) return;
                this.current = number;
                this.$nextTick(() => this.$root.submit());
            },
            toggle() {
                this.open = !this.open;
                if (this.open) { this.query = ''; this.$nextTick(() => this.$refs.search?.focus()); }
            },
        }"
        @click.outside="open = false" @keydown.escape.stop="open = false">
        @csrf
    <input type="hidden" name="back" value="{{ $back }}">
        <input type="hidden" name="vendor" :value="current" value="{{ $vendor?->number }}">

        <button type="button" @click="toggle()" :aria-expanded="open.toString()" aria-haspopup="listbox"
            aria-label="{{ __('Proveedor') }}"
            class="shadow-theme-xs flex h-10 w-full items-center gap-2.5 rounded-lg border border-gray-300 bg-white ps-1.5 pe-3 text-start text-sm transition-colors hover:bg-gray-50 focus-visible:border-brand-300 focus-visible:ring-3 focus-visible:ring-brand-500/10 focus-visible:outline-none dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-white/5"
            :class="open && 'border-brand-300 ring-3 ring-brand-500/10 dark:border-brand-800'">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">{!! $vendorIcon !!}</span>
            <span class="min-w-0 flex-1 truncate">
                <span class="font-semibold text-gray-800 dark:text-white/90" x-text="selected.number">{{ $vendor?->number }}</span>
                <span class="text-gray-500 dark:text-gray-400" x-text="'· ' + selected.name">· {{ $vendor?->name }}</span>
            </span>
            <svg class="size-4 shrink-0 text-gray-500 transition-transform dark:text-gray-400" :class="open && 'rotate-180'"
                viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.79 7.4 10 12.6l5.21-5.2" /></svg>
        </button>

        <div x-show="open" x-cloak
            x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="shadow-theme-lg absolute end-0 top-full z-40 mt-2 w-full min-w-72 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            @if ($vendors->count() > 5)
                <div class="border-b border-gray-100 p-2 dark:border-gray-800">
                    <label for="vendor-search" class="sr-only">{{ __('Buscar proveedor') }}</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-gray-400"><x-svg.search /></span>
                        <input id="vendor-search" x-ref="search" x-model="query" type="search" autocomplete="off"
                            placeholder="{{ __('Buscar por número o nombre') }}"
                            class="h-9 w-full rounded-lg border border-gray-200 bg-transparent ps-9 pe-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-800 dark:text-white/90 dark:placeholder:text-white/30">
                    </div>
                </div>
            @endif
            <ul role="listbox" aria-label="{{ __('Proveedor') }}" class="custom-scrollbar max-h-72 overflow-y-auto p-1.5">
                @foreach ($vendors as $option)
                    <li role="option" x-show="matches({{ $option->number }}, @js($option->name))"
                        :aria-selected="({{ $option->number }} === current).toString()">
                        <button type="button" @click="choose({{ $option->number }})"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-start transition-colors hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
                            :class="{{ $option->number }} === current && 'bg-brand-50 dark:bg-brand-500/10'">
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold"
                                    :class="{{ $option->number }} === current ? 'text-brand-600 dark:text-brand-400' : 'text-gray-800 dark:text-white/90'">{{ $option->number }}</span>
                                <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $option->name }}</span>
                            </span>
                            <svg x-show="{{ $option->number }} === current" class="size-4 shrink-0 text-brand-500 dark:text-brand-400"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10" /></svg>
                        </button>
                    </li>
                @endforeach
                <li x-show="filtered.length === 0" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('Ningún proveedor coincide.') }}</li>
            </ul>
        </div>
        <noscript><button type="submit" class="mt-2 text-sm text-brand-600 dark:text-brand-400">{{ __('Cambiar') }}</button></noscript>
    </form>
@elseif ($vendor)
    <p class="flex h-10 min-w-0 flex-1 items-center gap-2.5 rounded-lg border border-gray-200 ps-1.5 pe-3 text-sm sm:flex-none dark:border-gray-800">
        <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">{!! $vendorIcon !!}</span>
        <span class="truncate"><span class="font-semibold text-gray-800 dark:text-white/90">{{ $vendor->number }}</span>
            <span class="text-gray-500 dark:text-gray-400">· {{ $vendor->name }}</span></span>
    </p>
@endif
