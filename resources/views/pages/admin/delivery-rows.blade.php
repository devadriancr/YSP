@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="$title" />

    <x-admin.list-card :paginator="$rows" :action="route('admin.delivery-rows.index')" :filtered="$filtered"
        empty="Aún no hay filas de instrucción de entrega.">
        <x-slot:filters>
            <x-admin.filter :label="__('Proveedor')" name="proveedor" type="select" :options="$vendors" :value="$vendor" class="lg:w-72" />
            <x-admin.filter :label="__('Origen')" name="origen" type="select" :options="$origins" :value="$origin" class="lg:w-56" />
            <x-admin.filter :label="__('Buscar')" name="buscar" :value="$search" :placeholder="__('Número de parte u orden de compra')" class="lg:w-56" />
            <x-admin.filter :label="__('Desde')" name="desde" type="date" :value="$from" />
            <x-admin.filter :label="__('Hasta')" name="hasta" type="date" :value="$to" />
        </x-slot:filters>

        <x-slot:head>
            <x-admin.th>{{ __('Fecha') }}</x-admin.th>
            <x-admin.th>{{ __('Número de parte') }}</x-admin.th>
            <x-admin.th>{{ __('Proveedor') }}</x-admin.th>
            <x-admin.th>{{ __('Origen') }}</x-admin.th>
            <x-admin.th>{{ __('Orden de compra') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Cantidad') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Cajas') }}</x-admin.th>
        </x-slot:head>

        @foreach ($rows as $row)
            @php
                $snp = $row->partNumber->standard_pack_quantity ?: 1;
                $quantity = (int) round((float) $row->quantity);
            @endphp
            <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                <td class="text-theme-sm px-5 py-4 whitespace-nowrap text-gray-800 sm:px-6 dark:text-white/90">{{ $row->due_date->format('d/m/Y') }}</td>
                <td class="text-theme-sm px-5 py-4 font-medium whitespace-nowrap text-gray-800 sm:px-6 dark:text-white/90">{{ \App\Support\PartNumber::format($row->partNumber->number, $row->partNumber->project) }}</td>
                <td class="text-theme-xs px-5 py-4 text-gray-500 sm:px-6 dark:text-gray-400">{{ $row->vendor->number }}</td>
                <td class="px-5 py-4 sm:px-6">
                    @if ($row->origin === 'FR')
                        <x-ui.badge color="warning" size="sm">{{ __('Forecast') }}</x-ui.badge>
                    @elseif ($row->origin === 'FP')
                        <x-ui.badge color="info" size="sm">{{ __('Planeada') }}</x-ui.badge>
                    @else
                        <x-ui.badge color="primary" size="sm">{{ __('Orden de compra') }}</x-ui.badge>
                    @endif
                </td>
                <td class="text-theme-sm px-5 py-4 whitespace-nowrap text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ $row->number ? "{$row->number} - {$row->line}" : '—' }}</td>
                <td class="text-theme-sm px-5 py-4 text-end text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ number_format($quantity) }}</td>
                <td class="text-theme-sm px-5 py-4 text-end font-medium text-gray-800 tabular-nums sm:px-6 dark:text-white/90">{{ max(1, (int) ceil($quantity / $snp)) }}</td>
            </tr>
        @endforeach
    </x-admin.list-card>
@endsection
