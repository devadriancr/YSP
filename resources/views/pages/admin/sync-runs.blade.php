@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="$title" />

    {{-- Last 24 hours at a glance --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $last = $summary['last'];
            $cards = [
                ['label' => 'Última sincronización', 'value' => $last?->started_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? __('Nunca')],
                ['label' => 'Correctas (24 h)', 'value' => number_format($summary['done'])],
                ['label' => 'Con error (24 h)', 'value' => number_format($summary['failed']), 'error' => $summary['failed'] > 0],
                ['label' => 'Filas recibidas (24 h)', 'value' => number_format($summary['rows'])],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($card['label']) }}</p>
                <p @class([
                    'mt-2 text-title-sm font-semibold',
                    'text-error-600 dark:text-error-400' => $card['error'] ?? false,
                    'text-gray-800 dark:text-white/90' => ! ($card['error'] ?? false),
                ])>{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <x-admin.list-card :paginator="$runs" :action="route('admin.sync-runs.index')" :filtered="$filtered"
        empty="Aún no llega ninguna sincronización desde Supply Relay.">
        <x-slot:filters>
            <x-admin.filter :label="__('Estado')" name="estado" type="select" :options="$statuses" :value="$status" class="lg:w-44" />
            <x-admin.filter :label="__('Tipo')" name="tipo" type="select" :options="$types" :value="$type" class="lg:w-48" />
            <x-admin.filter :label="__('Proveedor')" name="proveedor" type="select" :options="$vendors" :value="$vendor" class="lg:w-72" />
            <x-admin.filter :label="__('Desde')" name="desde" type="date" :value="$from" />
            <x-admin.filter :label="__('Hasta')" name="hasta" type="date" :value="$to" />
        </x-slot:filters>

        <x-slot:head>
            <x-admin.th>{{ __('Hora de inicio') }}</x-admin.th>
            <x-admin.th>{{ __('Tipo') }}</x-admin.th>
            <x-admin.th>{{ __('Proveedor') }}</x-admin.th>
            <x-admin.th>{{ __('Estado') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Filas') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Duración') }}</x-admin.th>
            <x-admin.th>{{ __('Detalle') }}</x-admin.th>
        </x-slot:head>

        @foreach ($runs as $run)
            <tr class="border-b border-gray-100 align-top last:border-b-0 dark:border-gray-800">
                <td class="text-theme-sm px-5 py-4 whitespace-nowrap text-gray-800 sm:px-6 dark:text-white/90">
                    {{ $run->started_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}
                </td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ __($types[$run->type] ?? $run->type) }}</td>
                <td class="text-theme-xs px-5 py-4 text-gray-500 sm:px-6 dark:text-gray-400">
                    {{ $run->vendor ? "{$run->vendor->number} · {$run->vendor->name}" : '—' }}
                </td>
                <td class="px-5 py-4 sm:px-6">
                    @if ($run->status === \App\Models\SyncRun::DONE)
                        <x-ui.badge color="success" size="sm">{{ __('Correcta') }}</x-ui.badge>
                    @elseif ($run->status === \App\Models\SyncRun::FAILED)
                        <x-ui.badge color="error" size="sm">{{ __('Con error') }}</x-ui.badge>
                    @else
                        <x-ui.badge color="warning" size="sm">{{ __('En curso') }}</x-ui.badge>
                    @endif
                </td>
                <td class="text-theme-sm px-5 py-4 text-end text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ number_format($run->rows_received) }}</td>
                <td class="text-theme-sm px-5 py-4 text-end whitespace-nowrap text-gray-500 tabular-nums sm:px-6 dark:text-gray-400">
                    @if ($run->finished_at)
                        {{ max(0, round($run->started_at->diffInMilliseconds($run->finished_at) / 1000, 1)) }} s
                    @else
                        —
                    @endif
                </td>
                <td class="max-w-md px-5 py-4 sm:px-6">
                    @if ($run->error)
                        <details class="group">
                            <summary class="text-theme-sm cursor-pointer list-none font-medium text-error-600 marker:hidden dark:text-error-400">
                                {{ __('Ver error') }}
                                <svg class="ms-1 inline size-4 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                            </summary>
                            <p class="text-theme-xs mt-2 rounded-lg bg-error-50 px-3 py-2 break-words text-error-700 dark:bg-error-500/15 dark:text-error-400">{{ $run->error }}</p>
                        </details>
                    @else
                        <span class="text-theme-xs text-gray-400 dark:text-gray-500">—</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-admin.list-card>
@endsection
