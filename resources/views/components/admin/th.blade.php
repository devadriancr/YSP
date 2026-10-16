@props(['align' => 'start'])

<th {{ $attributes->merge(['class' => 'px-5 py-3 sm:px-6 ' . ($align === 'end' ? 'text-end' : 'text-start')]) }}>
    <p class="text-theme-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $slot }}</p>
</th>
