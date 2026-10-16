@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Usuarios" />

    @if (session('status'))
        <div class="mb-6"><x-ui.alert variant="success" :message="session('status')" /></div>
    @endif
    @if (session('error'))
        <div class="mb-6"><x-ui.alert variant="error" :message="session('error')" /></div>
    @endif

    {{-- One delete modal for the whole table; each row fills it in --}}
    <div x-data="{ open: false, action: '', name: '' }"
        @confirm-delete.window="action = $event.detail.action; name = $event.detail.name; open = true; $nextTick(() => $refs.cancel.focus())"
        class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">

        <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <form method="GET" action="{{ route('users.index') }}" class="relative w-full sm:max-w-sm" role="search">
                <label for="buscar" class="sr-only">{{ __('Buscar usuarios') }}</label>
                <span class="pointer-events-none absolute start-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                </span>
                <input id="buscar" name="buscar" type="search" value="{{ $search }}" placeholder="{{ __('Buscar por nombre, correo o vendor') }}"
                    class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 ps-11 pe-4 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
            </form>

            <a href="{{ route('users.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus-visible:ring-3 focus-visible:ring-brand-500/30 focus-visible:outline-none">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                {{ __('Nuevo usuario') }}
            </a>
        </div>

        <div class="custom-scrollbar max-w-full overflow-x-auto border-t border-gray-100 dark:border-gray-800">
            <table class="w-full min-w-[760px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-start sm:px-6"><p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Usuario') }}</p></th>
                        <th class="px-5 py-3 text-start sm:px-6"><p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Rol') }}</p></th>
                        <th class="px-5 py-3 text-start sm:px-6"><p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Proveedores') }}</p></th>
                        <th class="px-5 py-3 text-start sm:px-6"><p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Alta') }}</p></th>
                        <th class="px-5 py-3 sm:px-6"><span class="sr-only">{{ __('Acciones') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <x-common.user-avatar :user="$user" size="size-10 text-sm" />
                                    <div class="min-w-0">
                                        <p class="text-theme-sm truncate font-medium text-gray-800 dark:text-white/90">
                                            {{ $user->name }}
                                            @if ($user->is(auth()->user()))<span class="text-theme-xs font-normal text-gray-500 dark:text-gray-400">({{ __('tú') }})</span>@endif
                                        </p>
                                        <p class="text-theme-xs truncate text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                @if ($user->isAdmin())
                                    <x-ui.badge color="primary" size="sm">{{ __('Administrador') }}</x-ui.badge>
                                @elseif ($user->role === 'planner')
                                    <x-ui.badge color="info" size="sm">{{ __('Planeador') }}</x-ui.badge>
                                @else
                                    <x-ui.badge color="light" size="sm">{{ __('Proveedor') }}</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex max-w-xs flex-wrap gap-1.5">
                                    @forelse ($user->vendors as $vendor)
                                        <span title="{{ $vendor->name }}"
                                            class="text-theme-xs rounded-full bg-gray-100 px-2.5 py-0.5 font-medium text-gray-700 dark:bg-white/5 dark:text-white/80">
                                            {{ $vendor->number }}
                                        </span>
                                    @empty
                                        @if ($user->seesAllVendors())
                                            <span class="text-theme-xs text-gray-500 dark:text-gray-400">{{ __('Todos los proveedores') }}</span>
                                        @else
                                            <x-ui.badge color="warning" size="sm">{{ __('Sin proveedor asignado') }}</x-ui.badge>
                                        @endif
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-theme-sm px-5 py-4 text-gray-500 sm:px-6 dark:text-gray-400">
                                {{ $user->created_at?->format('d/m/Y') }}
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('users.edit', $user) }}" title="{{ __('Editar') }}" aria-label="{{ __('Editar :name', ['name' => $user->name]) }}"
                                        class="flex size-9 items-center justify-center rounded-lg border border-gray-300 text-gray-500 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-800 dark:hover:bg-brand-500/15 dark:hover:text-brand-400">
                                        <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.83 2.83 0 0 0-4-4L4 16v4zM13.5 6.5l4 4" /></svg>
                                    </a>
                                    @if ($user->is(auth()->user()))
                                        <span title="{{ __('No puedes eliminar tu propio usuario') }}"
                                            class="flex size-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 text-gray-300 dark:border-gray-800 dark:text-gray-600">
                                            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3" /></svg>
                                        </span>
                                    @else
                                        <button type="button" title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar :name', ['name' => $user->name]) }}"
                                            @click="$dispatch('confirm-delete', { action: @js(route('users.destroy', $user)), name: @js($user->name) })"
                                            class="flex size-9 items-center justify-center rounded-lg border border-gray-300 text-gray-500 transition-colors hover:border-error-300 hover:bg-error-50 hover:text-error-600 dark:border-gray-700 dark:text-gray-400 dark:hover:border-error-800 dark:hover:bg-error-500/15 dark:hover:text-error-400">
                                            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $search !== '' ? __('Ningún usuario coincide con la búsqueda.') : __('Todavía no hay usuarios.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-common.pagination :paginator="$users" />

        {{-- Delete confirmation --}}
        <template x-teleport="body">
            <div x-show="open" x-cloak @keydown.escape.window="open = false"
                class="fixed inset-0 z-99999 flex items-center justify-center p-4"
                role="dialog" aria-modal="true" aria-labelledby="delete-title">
                <div @click="open = false" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm" x-transition.opacity></div>
                <div x-show="open" x-transition
                    class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex gap-4">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3" /></svg>
                        </span>
                        <div>
                            <h3 id="delete-title" class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ __('¿Eliminar usuario?') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                x-text="@js(__('Se eliminará la cuenta de :name y ya no podrá entrar al portal. Esta acción no se puede deshacer.')).replace(':name', name)"></p>
                        </div>
                    </div>
                    <form method="POST" :action="action" class="mt-6 flex justify-end gap-3">
                        @csrf
                        @method('DELETE')
                        <button type="button" x-ref="cancel" @click="open = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                            {{ __('Cancelar') }}
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-error-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-error-600">
                            {{ __('Sí, eliminar') }}
                        </button>
                    </form>
                </div>
            </div>
        </template>
    </div>
@endsection
