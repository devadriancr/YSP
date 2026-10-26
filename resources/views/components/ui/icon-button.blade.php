@props([
    // Read by screen readers and shown as a tooltip: the button has no visible text
    'label',
    'variant' => 'outline',
    'size' => 'md',
])

@php
    $variants = [
        'outline' => 'border border-gray-300 text-gray-600 hover:bg-gray-50 hover:text-gray-800 focus-visible:ring-brand-500/20 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white/90',
        'ghost' => 'text-gray-400 hover:bg-gray-100 hover:text-gray-700 focus-visible:ring-brand-500/20 dark:hover:bg-white/5 dark:hover:text-white',
        'brand-outline' => 'border border-brand-300 text-brand-500 hover:bg-brand-50 focus-visible:ring-brand-500/20 dark:border-brand-500/40 dark:text-brand-400 dark:hover:bg-brand-500/10',
    ];
    $sizes = ['sm' => 'size-8', 'md' => 'size-9'];
@endphp

<button type="button" aria-label="{{ $label }}" title="{{ $label }}"
    {{ $attributes->class([
        'inline-flex shrink-0 items-center justify-center rounded-lg transition-colors focus-visible:ring-3 focus-visible:outline-none disabled:cursor-wait disabled:opacity-60',
        $sizes[$size] ?? $sizes['md'],
        $variants[$variant] ?? $variants['outline'],
    ]) }}>
    {{ $slot }}
</button>
