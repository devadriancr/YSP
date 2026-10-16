@extends('layouts.app')

@php
    $editing = $user->exists;
    // After a failed save keep what was chosen, even if it was "none"
    $selectedVendors = array_map('intval', session()->hasOldInput()
        ? old('vendors', [])
        : ($user->vendors?->pluck('id')->all() ?? []));
    $vendorError = $errors->first('vendors') ?: $errors->first('vendors.*');
    $roleError = $errors->first('role');
@endphp

@section('content')
    <x-common.page-breadcrumb :pageTitle="$title" />

    <form method="POST" action="{{ $editing ? route('users.update', $user) : route('users.store') }}" novalidate
        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="px-6 py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                {{ $editing ? __('Datos de :name', ['name' => $user->name]) : __('Datos del usuario') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Los campos con * son obligatorios.') }}
            </p>
        </div>

        <div x-data="{ role: @js(old('role', $user->role)) }"
            class="grid grid-cols-1 gap-5 border-t border-gray-100 p-4 sm:p-6 lg:grid-cols-2 dark:border-gray-800">
            <x-form.field :label="__('Nombre')" name="name" :value="$user->name" :placeholder="__('Nombre y apellido')"
                autocomplete="name" required />

            <x-form.field :label="__('Correo')" name="email" type="email" :value="$user->email"
                placeholder="usuario@proveedor.com" autocomplete="email" required />

            {{-- Role --}}
            <div>
                <label for="role" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    {{ __('Rol') }} <span class="text-error-500">*</span>
                </label>
                <div class="relative z-20 bg-transparent">
                    <select id="role" name="role" x-model="role" @if ($roleError) aria-invalid="true" aria-describedby="role-error" @endif
                        @class([
                            'dark:bg-dark-900 shadow-theme-xs h-11 w-full appearance-none rounded-lg border bg-transparent bg-none py-2.5 ps-4 pe-11 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90',
                            'border-error-300 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700' => $roleError,
                            'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800' => ! $roleError,
                        ])>
                        <option value="supplier" @selected(old('role', $user->role) === 'supplier') class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                            {{ __('Proveedor') }}
                        </option>
                        <option value="planner" @selected(old('role', $user->role) === 'planner') class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                            {{ __('Planeador') }}
                        </option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin') class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                            {{ __('Administrador') }}
                        </option>
                    </select>
                    <span class="pointer-events-none absolute end-4 top-1/2 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                        <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                @if ($roleError)
                    <p id="role-error" class="text-theme-xs text-error-500 mt-1.5">{{ $roleError }}</p>
                @endif
            </div>

            {{-- Vendors: multiple select of the template, with a search box --}}
            <div x-data="{
                    open: false,
                    query: '',
                    selected: @js($selectedVendors),
                    options: @js($vendors),
                    get filtered() {
                        const q = this.query.trim().toLowerCase();
                        return q ? this.options.filter(o => String(o.number).startsWith(q) || o.name.toLowerCase().includes(q)) : this.options;
                    },
                    label(id) {
                        const option = this.options.find(o => o.id === id);
                        return option ? option.number : id;
                    },
                    toggle(id) {
                        this.selected = this.selected.includes(id) ? this.selected.filter(i => i !== id) : [...this.selected, id];
                    },
                }" x-show="role === 'supplier'" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                <label id="vendors-label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    {{ __('Proveedores (vendors)') }}
                </label>

                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="vendors[]" :value="id">
                </template>

                <div @click="open = !open; $nextTick(() => open && $refs.search.focus())" role="button" tabindex="0"
                    @keydown.enter.prevent="open = !open" @keydown.space.prevent="open = !open"
                    aria-labelledby="vendors-label" :aria-expanded="open"
                    @class([
                        'shadow-theme-xs flex min-h-11 cursor-pointer gap-2 rounded-lg border bg-white px-3 py-2 transition focus:ring-3 focus:outline-hidden dark:bg-gray-900',
                        'border-error-300 focus:ring-error-500/10 dark:border-error-700' => $vendorError,
                        'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700' => ! $vendorError,
                    ])>
                    <div class="flex flex-1 flex-wrap items-center gap-2">
                        <template x-for="id in selected" :key="id">
                            <span class="flex items-center rounded-full bg-gray-100 py-1 ps-2.5 pe-2 text-sm text-gray-800 dark:bg-gray-800 dark:text-white/90">
                                <span x-text="label(id)"></span>
                                <button type="button" @click.stop="toggle(id)" :aria-label="@js(__('Quitar')) + ' ' + label(id)"
                                    class="ms-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                                    <svg class="size-3.5 fill-current" viewBox="0 0 14 14" aria-hidden="true">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.40717 4.46881C3.11428 4.17591 3.11428 3.70104 3.40717 3.40815C3.70006 3.11525 4.17494 3.11525 4.46783 3.40815L6.99943 5.93975L9.53095 3.40822C9.82385 3.11533 10.2987 3.11533 10.5916 3.40822C10.8845 3.70112 10.8845 4.17599 10.5916 4.46888L8.06009 7.00041L10.5916 9.53193C10.8845 9.82482 10.8845 10.2997 10.5916 10.5926C10.2987 10.8855 9.82385 10.8855 9.53095 10.5926L6.99943 8.06107L4.46783 10.5927C4.17494 10.8856 3.70006 10.8856 3.40717 10.5927C3.11428 10.2998 3.11428 9.8249 3.40717 9.53201L5.93877 7.00041L3.40717 4.46881Z" />
                                    </svg>
                                </button>
                            </span>
                        </template>
                        <span x-show="selected.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Selecciona uno o varios vendors…') }}
                        </span>
                    </div>
                    <svg class="mt-1.5 size-5 shrink-0 text-gray-500 transition-transform dark:text-gray-400" :class="open && 'rotate-180'"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <div x-show="open" x-cloak x-transition
                    class="shadow-theme-lg absolute start-0 top-full z-40 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-100 p-2 dark:border-gray-800">
                        <input x-ref="search" x-model="query" type="search" placeholder="{{ __('Buscar por número o nombre') }}"
                            class="h-9 w-full rounded-md border border-gray-200 bg-transparent px-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">
                    </div>
                    <ul class="custom-scrollbar max-h-60 overflow-y-auto py-1" role="listbox" aria-multiselectable="true">
                        <template x-for="option in filtered" :key="option.id">
                            <li role="option" :aria-selected="selected.includes(option.id)" @click="toggle(option.id)"
                                class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-white/5"
                                :class="selected.includes(option.id) ? 'text-brand-600 dark:text-brand-400' : 'text-gray-700 dark:text-gray-300'">
                                <span class="flex size-4 shrink-0 items-center justify-center rounded border"
                                    :class="selected.includes(option.id) ? 'border-brand-500 bg-brand-500 text-white' : 'border-gray-300 dark:border-gray-600'">
                                    <svg x-show="selected.includes(option.id)" class="size-3" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                                        <path d="M2.5 6l2.5 2.5 4.5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <span class="font-medium" x-text="option.number"></span>
                                <span class="truncate text-gray-500 dark:text-gray-400" x-text="option.name"></span>
                            </li>
                        </template>
                        <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ count($vendors) ? __('Ningún vendor coincide con la búsqueda.') : __('Aún no hay vendors: se crean al sincronizar desde Supply Relay.') }}
                        </li>
                    </ul>
                </div>

                @if ($vendorError)
                    <p class="text-theme-xs text-error-500 mt-1.5">{{ $vendorError }}</p>
                @else
                    <p class="text-theme-xs mt-1.5 text-gray-500 dark:text-gray-400">
                        {{ __('El usuario solo verá las etiquetas y la instrucción de entrega de estos vendors.') }}
                    </p>
                @endif
            </div>

            <x-form.field :label="__('Contraseña')" name="password" type="password" autocomplete="new-password"
                :required="! $editing" :hint="$editing ? __('Déjala vacía para conservar la contraseña actual.') : __('Mínimo 8 caracteres.')" />

            <x-form.field :label="__('Confirmar contraseña')" name="password_confirmation" type="password"
                autocomplete="new-password" :required="! $editing" />
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-4 py-4 sm:flex-row sm:justify-end sm:px-6 dark:border-gray-800">
            <a href="{{ route('users.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                {{ __('Cancelar') }}
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus-visible:ring-3 focus-visible:ring-brand-500/30 focus-visible:outline-none">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10" /></svg>
                {{ $editing ? __('Guardar cambios') : __('Crear usuario') }}
            </button>
        </div>
    </form>
@endsection
