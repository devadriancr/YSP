@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="$title" />

    <x-admin.list-card :paginator="$parts" :action="route('admin.part-numbers.index')" :filtered="$filtered"
        empty="Aún no hay números de parte: llegan con cada proveedor sincronizado.">
        <x-slot:filters>
            <x-admin.filter :label="__('Proveedor')" name="proveedor" type="select" :options="$vendors" :value="$vendor" class="lg:w-80" />
            <x-admin.filter :label="__('Buscar')" name="buscar" :value="$search" :placeholder="__('Número, descripción o proyecto')" class="lg:w-72" />
        </x-slot:filters>

        <x-slot:head>
            <x-admin.th>{{ __('Número de parte') }}</x-admin.th>
            <x-admin.th>{{ __('Descripción') }}</x-admin.th>
            <x-admin.th>{{ __('Proveedor') }}</x-admin.th>
            <x-admin.th>{{ __('Proyecto') }}</x-admin.th>
            <x-admin.th>{{ __('Empaque') }}</x-admin.th>
            <x-admin.th align="end">{{ __('SNP') }}</x-admin.th>
            <x-admin.th>{{ __('Lado') }}</x-admin.th>
            <x-admin.th>{{ __('Siguiente proceso') }}</x-admin.th>
        </x-slot:head>

        @foreach ($parts as $part)
            <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                <td class="px-5 py-4 whitespace-nowrap sm:px-6">
                    <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ \App\Support\PartNumber::format($part->number, $part->project) }}</p>
                    <p class="text-theme-xs text-gray-400 dark:text-gray-500">{{ $part->number }}</p>
                </td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ $part->description ?? '—' }}</td>
                <td class="text-theme-xs px-5 py-4 text-gray-500 sm:px-6 dark:text-gray-400">{{ $part->vendor->number }} · {{ $part->vendor->name }}</td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ $part->project ?? '—' }}</td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ $part->standard_pack ?? '—' }}</td>
                <td class="text-theme-sm px-5 py-4 text-end text-gray-700 tabular-nums sm:px-6 dark:text-gray-300">{{ number_format($part->standard_pack_quantity) }}</td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ $part->side ?? '—' }}</td>
                <td class="text-theme-sm px-5 py-4 text-gray-700 sm:px-6 dark:text-gray-300">{{ $part->next_process ?? '—' }}</td>
            </tr>
        @endforeach
    </x-admin.list-card>
@endsection
