@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="$title" />

    <x-admin.list-card :paginator="$vendors" :action="route('admin.vendors.index')" :filtered="$filtered"
        empty="Aún no hay proveedores: se crean al sincronizar desde Supply Relay." minWidth="min-w-[1100px]">
        <x-slot:filters>
            <x-admin.filter :label="__('Buscar')" name="buscar" :value="$search" :placeholder="__('Número, nombre o RFC')" class="lg:w-80" />
        </x-slot:filters>

        <x-slot:head>
            <x-admin.th>{{ __('Proveedor') }}</x-admin.th>
            <x-admin.th>{{ __('RFC') }}</x-admin.th>
            <x-admin.th>{{ __('Dirección') }}</x-admin.th>
            <x-admin.th>{{ __('Contacto') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Partes') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Órdenes de compra') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Instrucción de entrega') }}</x-admin.th>
            <x-admin.th align="end">{{ __('Usuarios') }}</x-admin.th>
            <x-admin.th>{{ __('Sincronizado') }}</x-admin.th>
        </x-slot:head>

        @foreach ($vendors as $vendor)
            <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                <td class="px-5 py-4 sm:px-6">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $vendor->number }}</p>
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $vendor->name }}</p>
                </td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ $vendor->tax_id ?? '—' }}</td>
                <td class="text-theme-xs px-5 py-4 text-gray-500 sm:px-6 dark:text-gray-400">
                    {{ collect([$vendor->street, $vendor->locality, $vendor->region, $vendor->state, $vendor->postal_code])->filter()->implode(', ') ?: '—' }}
                </td>
                <td class="text-theme-xs px-5 py-4 text-gray-500 sm:px-6 dark:text-gray-400">
                    <p class="text-gray-700 dark:text-gray-300">{{ $vendor->contact_name ?? '—' }}</p>
                    <p>{{ collect([$vendor->email, $vendor->phone])->filter()->implode(' · ') }}</p>
                </td>
                <td class="text-theme-sm px-5 py-4 text-end text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ number_format($vendor->part_numbers_count) }}</td>
                <td class="text-theme-sm px-5 py-4 text-end text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ number_format($vendor->purchase_order_lines_count) }}</td>
                <td class="text-theme-sm px-5 py-4 text-end text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ number_format($vendor->delivery_rows_count) }}</td>
                <td class="text-theme-sm px-5 py-4 text-end text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ $vendor->users_count }}</td>
                <td class="text-theme-xs px-5 py-4 whitespace-nowrap text-gray-500 sm:px-6 dark:text-gray-400">
                    {{ $vendor->synced_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? __('Nunca') }}
                </td>
            </tr>
        @endforeach
    </x-admin.list-card>
@endsection
