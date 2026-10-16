@props(['paginator'])

{{-- "1–10 de 52" and page links with the template styles; keeps the filters in the URL --}}
@if ($paginator->total() > 0)
    @php
        $link = 'flex h-9 min-w-9 items-center justify-center rounded-lg px-3 text-sm font-medium';
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        // First, last and two around the current one; null is a gap (…)
        $pages = collect([1, $last, ...range(max(1, $current - 2), min($last, $current + 2))])->unique()->sort()->values();
    @endphp
    <div class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-gray-800">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
            {{ __(':first–:last de :total', ['first' => number_format($paginator->firstItem()), 'last' => number_format($paginator->lastItem()), 'total' => number_format($paginator->total())]) }}
        </p>
        @if ($paginator->hasPages())
            <nav class="flex flex-wrap items-center gap-1.5" aria-label="{{ __('Páginas') }}">
                @if ($paginator->onFirstPage())
                    <span class="{{ $link }} border border-gray-200 text-gray-300 dark:border-gray-800 dark:text-gray-600">{{ __('Anterior') }}</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $link }} border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/[0.03]">{{ __('Anterior') }}</a>
                @endif

                @foreach ($pages as $index => $number)
                    @if ($index > 0 && $number - $pages[$index - 1] > 1)
                        <span class="px-1 text-gray-400" aria-hidden="true">…</span>
                    @endif
                    @if ($number === $current)
                        <span aria-current="page" class="{{ $link }} bg-brand-500 text-white">{{ $number }}</span>
                    @else
                        <a href="{{ $paginator->url($number) }}" class="{{ $link }} text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-brand-500/15 dark:hover:text-brand-400">{{ $number }}</a>
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $link }} border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/[0.03]">{{ __('Siguiente') }}</a>
                @else
                    <span class="{{ $link }} border border-gray-200 text-gray-300 dark:border-gray-800 dark:text-gray-600">{{ __('Siguiente') }}</span>
                @endif
            </nav>
        @endif
    </div>
@endif
