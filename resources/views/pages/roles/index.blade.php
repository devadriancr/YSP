@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Roles" />

    @if (session('status'))
        <div class="mb-6"><x-ui.alert variant="success" :message="session('status')" /></div>
    @endif
    @if (session('error'))
        <div class="mb-6"><x-ui.alert variant="error" :message="session('error')" /></div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Cada rol define qué botones y pantallas puede usar el usuario.') }}</p>
            <a href="{{ route('roles.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus-visible:ring-3 focus-visible:ring-brand-500/30 focus-visible:outline-none">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                {{ __('Nuevo rol') }}
            </a>
        </div>

        <div class="custom-scrollbar max-w-full overflow-x-auto border-t border-gray-100 dark:border-gray-800">
            <table class="w-full min-w-[640px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-start sm:px-6"><p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Rol') }}</p></th>
                        <th class="px-5 py-3 text-start sm:px-6"><p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Permisos') }}</p></th>
                        <th class="px-5 py-3 text-start sm:px-6"><p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Usuarios') }}</p></th>
                        <th class="px-5 py-3 sm:px-6"><span class="sr-only">{{ __('Acciones') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                            <td class="text-theme-sm px-5 py-4 font-medium text-gray-800 sm:px-6 dark:text-white/90">{{ $role->name }}</td>
                            <td class="text-theme-sm px-5 py-4 text-gray-500 sm:px-6 dark:text-gray-400">
                                @if ($role->isAdmin())
                                    {{ __('Todos (no se puede cambiar)') }}
                                @else
                                    {{ count($role->permissions) }} {{ __('de') }} {{ count(config('permissions')) }}
                                @endif
                            </td>
                            <td class="text-theme-sm px-5 py-4 text-gray-500 sm:px-6 dark:text-gray-400">{{ $role->users_count }}</td>
                            <td class="px-5 py-4 sm:px-6">
                                @unless ($role->isAdmin())
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('roles.edit', $role) }}" title="{{ __('Editar') }}" aria-label="{{ __('Editar :name', ['name' => $role->name]) }}"
                                            class="flex size-9 items-center justify-center rounded-lg border border-gray-300 text-gray-500 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-800 dark:hover:bg-brand-500/15 dark:hover:text-brand-400">
                                            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.83 2.83 0 0 0-4-4L4 16v4zM13.5 6.5l4 4" /></svg>
                                        </a>
                                        @unless ($role->isBuiltIn() || $role->users_count)
                                            <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                                onsubmit="return confirm(@js(__('¿Eliminar el rol :name?', ['name' => $role->name])))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar :name', ['name' => $role->name]) }}"
                                                    class="flex size-9 items-center justify-center rounded-lg border border-gray-300 text-gray-500 transition-colors hover:border-error-300 hover:bg-error-50 hover:text-error-600 dark:border-gray-700 dark:text-gray-400 dark:hover:border-error-800 dark:hover:bg-error-500/15 dark:hover:text-error-400">
                                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3" /></svg>
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
