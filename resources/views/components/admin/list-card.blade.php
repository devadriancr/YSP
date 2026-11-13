@props([
    'paginator',
    // Where the filter form submits (the same list)
    'action',
    // Shown when there are no rows
    'empty' => null,
    'filtered' => false,
    'minWidth' => 'min-w-[900px]',
])

{{-- Read-only list for admins: filters on top, table in the middle, pagination below --}}
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    @isset($filters)
        <form method="GET" action="{{ $action }}"
            class="flex flex-col gap-4 rounded-t-2xl border-b border-gray-100 bg-gray-50/60 px-5 py-4 sm:px-6 lg:flex-row lg:flex-wrap lg:items-end dark:border-gray-800 dark:bg-white/[0.02]">
            {{ $filters }}
            <div class="flex gap-2 lg:ms-auto">
                <button type="submit"
                    class="inline-flex h-11 items-center gap-2 rounded-lg bg-brand-500 px-4 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus-visible:ring-3 focus-visible:ring-brand-500/30 focus-visible:outline-none">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18l-7 8.5V19l-4 2v-7.5z" /></svg>
                    {{ __('Filtrar') }}
                </button>
                @if ($filtered)
                    <a href="{{ $action }}"
                        class="shadow-theme-xs inline-flex h-11 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                        {{ __('Limpiar') }}
                    </a>
                @endif
            </div>
        </form>
    @endisset

    <div class="custom-scrollbar max-w-full overflow-x-auto">
        <table class="w-full {{ $minWidth }}">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    {{ $head }}
                </tr>
            </thead>
            <tbody>
                @if ($paginator->isEmpty())
                    <tr>
                        <td colspan="20" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ $filtered ? __('Ningún registro coincide con los filtros.') : __($empty ?? 'No hay registros.') }}
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    <x-common.pagination :paginator="$paginator" />
</div>
