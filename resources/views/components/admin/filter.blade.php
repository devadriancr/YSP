@props([
    'label',
    'name',
    // text | search | date | select
    'type' => 'search',
    'value' => null,
    'placeholder' => '',
    // select: [value => label]
    'options' => [],
    'all' => null,
])

{{-- One filter of an admin list, with the template input styles --}}
@php
    $class = 'dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800';
@endphp

<div {{ $attributes->merge(['class' => 'w-full lg:w-auto']) }}>
    <label for="filter-{{ $name }}" class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</label>
    <div class="relative">
        @if ($type === 'select')
            <select id="filter-{{ $name }}" name="{{ $name }}" class="{{ $class }} appearance-none ps-4 pe-10">
                <option value="">{{ $all ?? __('Todos') }}</option>
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ __($optionLabel) }}</option>
                @endforeach
            </select>
            <span class="pointer-events-none absolute end-3.5 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.79 7.4 10 12.6l5.21-5.2" /></svg>
            </span>
        @elseif ($type === 'date')
            <input id="filter-{{ $name }}" name="{{ $name }}" type="date" value="{{ $value }}" class="{{ $class }} px-4">
        @else
            <span class="pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
            </span>
            <input id="filter-{{ $name }}" name="{{ $name }}" type="search" value="{{ $value }}" placeholder="{{ $placeholder }}"
                class="{{ $class }} ps-11 pe-4">
        @endif
    </div>
</div>
