@extends('layouts.app')

@php
    $editing = $role->exists;
    // After a failed save keep what was ticked
    $granted = session()->hasOldInput() ? old('permissions', []) : $role->permissions;
@endphp

@section('content')
    <x-common.page-breadcrumb :pageTitle="$title" />

    <form method="POST" action="{{ $editing ? route('roles.update', $role) : route('roles.store') }}" novalidate
        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="px-6 py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">{{ $editing ? __('Datos de :name', ['name' => $role->name]) : __('Datos del rol') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Los campos con * son obligatorios.') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-5 border-t border-gray-100 p-4 sm:p-6 dark:border-gray-800">
            <div class="lg:max-w-md">
                <x-form.field :label="__('Nombre')" name="name" :value="$role->name" :placeholder="__('Cadena de suministro')" required />
            </div>

            <fieldset>
                <legend class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Permisos') }}</legend>
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    @foreach (config('permissions') as $key => $label)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $granted, true))
                                class="mt-0.5 size-4 shrink-0 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900">
                            <span>{{ __($label) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('permissions.*')<p class="text-theme-xs text-error-500 mt-1.5">{{ $message }}</p>@enderror
                <p class="text-theme-xs mt-3 text-gray-500 dark:text-gray-400">
                    {{ __('Un rol sin «Ver todos los proveedores» solo ve los proveedores asignados a cada usuario.') }}
                </p>
            </fieldset>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-4 py-4 sm:flex-row sm:justify-end sm:px-6 dark:border-gray-800">
            <a href="{{ route('roles.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                {{ __('Cancelar') }}
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus-visible:ring-3 focus-visible:ring-brand-500/30 focus-visible:outline-none">
                {{ $editing ? __('Guardar cambios') : __('Crear rol') }}
            </button>
        </div>
    </form>
@endsection
