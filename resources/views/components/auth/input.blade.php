@props(['label', 'name', 'id' => null, 'type' => 'text', 'value' => null, 'autocomplete' => null, 'icon' => null])

@php $id ??= $name; @endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        {{ $label }}
    </label>
    <div class="relative">
        @if ($icon)
            <span class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-gray-400">{{ $icon }}</span>
        @endif
        <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" required
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            {{ $attributes->class([
                'shadow-theme-xs h-11 w-full rounded-lg border bg-transparent py-2.5 pe-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                $icon ? 'ps-9' : 'ps-4',
                'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800' => ! $errors->has($name),
                'border-error-500 focus:border-error-500 focus:ring-error-500/10 dark:border-error-500' => $errors->has($name),
            ]) }} />
    </div>
</div>
