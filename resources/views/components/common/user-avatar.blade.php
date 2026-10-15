@props([
    'user' => auth()->user(),
    // Tailwind size + text classes, e.g. 'size-11 text-sm'
    'size' => 'size-11 text-sm',
])

@php
    $initials = collect(preg_split('/\s+/', trim($user?->name ?? '')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->merge(['class' => "$size flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-100 font-semibold text-brand-600 dark:bg-brand-500/20 dark:text-brand-400"]) }}
    title="{{ $user?->name }}">
    {{ $initials ?: '?' }}
</span>
