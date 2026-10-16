@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="$title" />

    <x-admin.list-card :paginator="$calendars" :action="route('admin.work-calendars.index')" minWidth="min-w-[760px]"
        empty="Aún no hay calendarios: Supply Relay los envía en cada sincronización completa.">
        <x-slot:head>
            <x-admin.th>{{ __('Año') }}</x-admin.th>
            <x-admin.th>{{ __('Planta') }}</x-admin.th>
            <x-admin.th>{{ __('Centro de trabajo') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Laborales') }}</x-admin.th>
            <x-admin.th align="end">{{ __('No laborales') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Medio turno') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Descanso') }}</x-admin.th>
            <x-admin.th>{{ __('Sincronizado') }}</x-admin.th>
        </x-slot:head>

        @foreach ($calendars as $calendar)
            @php $isShown = [$calendar->facility, $calendar->work_center] === $shown; @endphp
            <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                <td class="text-theme-sm px-5 py-4 font-medium text-gray-800 sm:px-6 dark:text-white/90">{{ $calendar->year }}</td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ $calendar->facility }}</td>
                <td class="px-5 py-4 sm:px-6">
                    <span class="text-theme-sm text-gray-700 dark:text-gray-300">{{ $calendar->work_center }}</span>
                    @if ($isShown)
                        <span class="ms-2"><x-ui.badge color="success" size="sm">{{ __('En el portal') }}</x-ui.badge></span>
                    @endif
                </td>
                <td class="text-theme-sm px-5 py-4 text-end text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ $calendar->counts['working'] }}</td>
                <td class="text-theme-sm px-5 py-4 text-end text-error-600 tabular-nums sm:px-6 dark:text-error-400">{{ $calendar->counts['holiday'] }}</td>
                <td class="text-theme-sm px-5 py-4 text-end text-brand-600 tabular-nums sm:px-6 dark:text-brand-400">{{ $calendar->counts['half'] }}</td>
                <td class="text-theme-sm px-5 py-4 text-end text-gray-500 tabular-nums sm:px-6 dark:text-gray-400">{{ $calendar->counts['rest'] }}</td>
                <td class="text-theme-xs px-5 py-4 whitespace-nowrap text-gray-500 sm:px-6 dark:text-gray-400">
                    {{ $calendar->synced_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                </td>
            </tr>
        @endforeach
    </x-admin.list-card>
@endsection
