@props([
    'label',
    'name',
    // [value => label]
    'options' => [],
    // values already chosen (from the query string)
    'selected' => [],
    // Text of the button when nothing is chosen
    'all' => null,
])

{{-- Filter that takes several values: a dropdown of checkboxes that submits "name[]=a&name[]=b".
     With many options (vendors) it gets a search box. --}}
@php
    $choices = collect($options)->map(fn ($text, $value) => ['value' => (string) $value, 'label' => __($text)])->values();
@endphp

<div {{ $attributes->merge(['class' => 'w-full lg:w-auto']) }}
    x-data="{
        open: false,
        query: '',
        options: @js($choices),
        chosen: @js(array_map('strval', array_values((array) $selected))),
        get shown() {
            const q = this.query.trim().toLowerCase();
            return q ? this.options.filter(o => o.label.toLowerCase().includes(q)) : this.options;
        },
        get summary() {
            if (this.chosen.length === 0) return @js($all ?? __('Todos'));
            if (this.chosen.length === 1) return this.options.find(o => o.value === this.chosen[0])?.label ?? this.chosen[0];
            return @js(__(':count seleccionados')).replace(':count', this.chosen.length);
        },
        toggle(value) {
            this.chosen = this.chosen.includes(value) ? this.chosen.filter(v => v !== value) : [...this.chosen, value];
        },
    }"
    @click.outside="open = false" @keydown.escape.stop="open = false">
    <label id="filter-{{ $name }}-label" class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</label>

    <template x-for="value in chosen" :key="value"><input type="hidden" name="{{ $name }}[]" :value="value"></template>

    <div class="relative">
        <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="listbox" aria-labelledby="filter-{{ $name }}-label"
            class="shadow-theme-xs flex h-11 w-full items-center gap-2 rounded-lg border border-gray-300 bg-transparent ps-4 pe-3 text-start text-sm text-gray-800 transition focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800"
            :class="open && 'border-brand-300 ring-3 ring-brand-500/10 dark:border-brand-800'">
            <span class="min-w-0 flex-1 truncate" :class="chosen.length === 0 && 'text-gray-500 dark:text-gray-400'" x-text="summary"></span>
            <span x-show="chosen.length > 1" x-cloak class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-600 tabular-nums dark:bg-brand-500/15 dark:text-brand-400" x-text="chosen.length"></span>
            <svg class="size-5 shrink-0 text-gray-500 transition-transform dark:text-gray-400" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.79 7.4 10 12.6l5.21-5.2" /></svg>
        </button>

        <div x-show="open" x-cloak
            x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="shadow-theme-lg absolute start-0 top-full z-20 mt-2 w-full min-w-64 origin-top rounded-xl border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-gray-900">
            @if ($choices->count() > 6)
                <div class="relative mb-2">
                    <span class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-gray-400"><x-svg.search /></span>
                    <input type="search" x-model="query" placeholder="{{ __('Buscar') }}" aria-label="{{ __('Buscar') }}"
                        class="h-9 w-full rounded-lg border border-gray-200 bg-transparent ps-9 pe-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
                </div>
            @endif
            <ul role="listbox" aria-multiselectable="true" aria-labelledby="filter-{{ $name }}-label" class="custom-scrollbar max-h-60 overflow-y-auto">
                <template x-for="option in shown" :key="option.value">
                    <li role="option" :aria-selected="chosen.includes(option.value).toString()">
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">
                            <input type="checkbox" :checked="chosen.includes(option.value)" @change="toggle(option.value)"
                                class="size-4 shrink-0 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900">
                            <span class="min-w-0 flex-1 truncate" x-text="option.label"></span>
                        </label>
                    </li>
                </template>
                <li x-show="shown.length === 0" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('Nada coincide con la búsqueda.') }}</li>
            </ul>
            <div x-show="chosen.length > 0" x-cloak class="mt-2 border-t border-gray-100 pt-2 dark:border-gray-800">
                <button type="button" @click="chosen = []" class="w-full rounded-lg px-3 py-1.5 text-start text-xs font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5">{{ __('Quitar selección') }}</button>
            </div>
        </div>
    </div>
</div>
