@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="$title" />

    <x-admin.list-card :paginator="$activity" :action="route('admin.activity.index')" :filtered="$filtered"
        empty="Aún no hay descargas ni consultas de documentos nuevos.">
        <x-slot:filters>
            <x-admin.filter :label="__('Proveedor')" name="proveedor" type="select" :options="$vendors" :value="$vendor" class="lg:w-72" />
            <x-admin.filter :label="__('Tipo')" name="tipo" type="select" :options="$types" :value="$type" class="lg:w-48" />
            <x-admin.filter :label="__('Desde')" name="desde" type="date" :value="$from" />
            <x-admin.filter :label="__('Hasta')" name="hasta" type="date" :value="$to" />
        </x-slot:filters>

        <x-slot:head>
            <x-admin.th>{{ __('Fecha y hora') }}</x-admin.th>
            <x-admin.th>{{ __('Proveedor') }}</x-admin.th>
            <x-admin.th>{{ __('Usuario') }}</x-admin.th>
            <x-admin.th>{{ __('Acción') }}</x-admin.th>
            <x-admin.th>{{ __('Documento') }}</x-admin.th>
            <x-admin.th>{{ __('Día del documento') }}</x-admin.th>
        </x-slot:head>

        @foreach ($activity as $row)
            <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                <td class="text-theme-sm px-5 py-4 whitespace-nowrap text-gray-800 sm:px-6 dark:text-white/90">{{ $row->at->format('d/m/Y H:i:s') }}</td>
                <td class="text-theme-xs px-5 py-4 text-gray-500 sm:px-6 dark:text-gray-400">{{ $row->vendor ? "{$row->vendor->number} · {$row->vendor->name}" : '—' }}</td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ $row->user_name }}</td>
                <td class="px-5 py-4 sm:px-6">
                    <x-ui.badge :color="$row->kind === 'download' ? 'success' : 'info'" size="sm">{{ __($types[$row->kind]) }}</x-ui.badge>
                </td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ $row->document === 'labels' ? __('Etiquetas') : __('Instrucción de entrega') }}</td>
                <td class="text-theme-sm px-5 py-4 whitespace-nowrap text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ $row->date->format('d/m/Y') }}</td>
            </tr>
        @endforeach
    </x-admin.list-card>
@endsection
