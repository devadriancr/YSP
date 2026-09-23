@extends('layouts.fullscreen-layout')

@section('content')
    <x-auth.layout :title="__('Verificación en dos pasos')">
        <div x-data="{ recovery: false }">
            <p class="mb-5 text-center text-sm text-gray-500 dark:text-gray-400" x-show="! recovery">
                {{ __('Ingresa el código de tu aplicación de autenticación.') }}
            </p>
            <p class="mb-5 text-center text-sm text-gray-500 dark:text-gray-400" x-cloak x-show="recovery">
                {{ __('Ingresa uno de tus códigos de recuperación de emergencia.') }}
            </p>

            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf
                <div class="space-y-5">
                    <div x-show="! recovery">
                        <label for="code" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Código') }}</label>
                        <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus x-ref="code"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>
                    <div x-cloak x-show="recovery">
                        <label for="recovery_code" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Código de recuperación') }}</label>
                        <input type="text" id="recovery_code" name="recovery_code" autocomplete="one-time-code" x-ref="recovery_code"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>

                    <x-auth.button>{{ __('Verificar') }}</x-auth.button>

                    <div class="text-center">
                        <button type="button" class="text-brand-500 hover:text-brand-600 dark:text-brand-400 text-sm" x-show="! recovery"
                            x-on:click="recovery = true; $nextTick(() => $refs.recovery_code.focus())">
                            {{ __('Usar un código de recuperación') }}
                        </button>
                        <button type="button" class="text-brand-500 hover:text-brand-600 dark:text-brand-400 text-sm" x-cloak x-show="recovery"
                            x-on:click="recovery = false; $nextTick(() => $refs.code.focus())">
                            {{ __('Usar un código de autenticación') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </x-auth.layout>
@endsection
