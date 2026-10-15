@props([
    // Classes for the text label (e.g. to hide it when the sidebar is collapsed)
    'labelClass' => '',
])

<span {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-white">
        <x-svg.clickup-brain class="size-5" />
    </span>
    <span class="{{ $labelClass }} whitespace-nowrap text-lg font-semibold text-gray-800 dark:text-white/90">
        {{ __('Portal de Proveedores') }}
    </span>
</span>
