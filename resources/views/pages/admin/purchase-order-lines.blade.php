@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="$title" />

    <x-admin.list-card :paginator="$lines" :action="route('admin.purchase-order-lines.index')" :filtered="$filtered"
        empty="Aún no hay líneas de orden de compra.">
        <x-slot:filters>
            <x-admin.filter :label="__('Proveedor')" name="proveedor" type="select" :options="$vendors" :value="$vendor" class="lg:w-72" />
            <x-admin.filter :label="__('Buscar')" name="buscar" :value="$search" :placeholder="__('Número de parte u orden de compra')" class="lg:w-60" />
            <x-admin.filter :label="__('Entrega desde')" name="desde" type="date" :value="$from" />
            <x-admin.filter :label="__('Entrega hasta')" name="hasta" type="date" :value="$to" />
        </x-slot:filters>

        <x-slot:head>
            <x-admin.th>{{ __('Entrega') }}</x-admin.th>
            <x-admin.th>{{ __('Orden de compra') }}</x-admin.th>
            <x-admin.th>{{ __('Número de parte') }}</x-admin.th>
            <x-admin.th>{{ __('Proveedor') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Cantidad') }}</x-admin.th>
            <x-admin.th align="end">{{ __('SNP') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Cajas') }}</x-admin.th>
            <x-admin.th>{{ __('Turno') }}</x-admin.th>
            <x-admin.th>{{ __('Almacén') }}</x-admin.th>
        </x-slot:head>

        @foreach ($lines as $line)
            @php
                $snp = $line->partNumber->standard_pack_quantity;
                $quantity = (int) round((float) $line->quantity);
            @endphp
            <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                <td class="text-theme-sm px-5 py-4 whitespace-nowrap text-gray-800 sm:px-6 dark:text-white/90">{{ $line->due_date->format('d/m/Y') }}</td>
                <td class="text-theme-sm px-5 py-4 whitespace-nowrap text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ $line->number }} - {{ $line->line }}</td>
                <td class="px-5 py-4 sm:px-6">
                    <p class="text-theme-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90">{{ \App\Support\PartNumber::format($line->partNumber->number, $line->partNumber->project) }}</p>
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $line->partNumber->description }}</p>
                </td>
                <td class="text-theme-xs px-5 py-4 text-gray-500 sm:px-6 dark:text-gray-400">{{ $line->vendor->number }}</td>
                <td class="text-theme-sm px-5 py-4 text-end text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ number_format($quantity) }}</td>
                <td class="text-theme-sm px-5 py-4 text-end text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ number_format($snp) }}</td>
                <td class="text-theme-sm px-5 py-4 text-end font-medium text-gray-800 tabular-nums sm:px-6 dark:text-white/90">{{ $snp > 0 ? (int) ceil($quantity / $snp) : 1 }}</td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ $line->shift ?? '—' }}</td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ $line->warehouse ?? '—' }}</td>
            </tr>
        @endforeach
    </x-admin.list-card>
@endsection
