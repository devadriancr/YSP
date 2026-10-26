@extends('layouts.fullscreen-layout')

@section('content')
    <x-auth.layout :title="__('Iniciar Sesión')" hide-title>
        <form method="POST" action="{{ route('login.store') }}" class="space-y-5"
            x-data="{ sending: false, showPassword: false }" @submit="sending = true">
            @csrf
            <x-auth.device-fields />

            <x-auth.input :label="__('Correo Electrónico')" name="email" type="email" :value="old('email')" autocomplete="username" autofocus placeholder="Ejemplo@mail.com">
                <x-slot:icon>
                    <svg class="h-4 w-4 stroke-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </x-slot:icon>
            </x-auth.input>

            <!-- Password -->
            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    {{ __('Contraseña') }}
                </label>
                <div class="relative">
                    <svg class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 stroke-current text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    <input :type="showPassword ? 'text' : 'password'" type="password"
                        id="password" name="password" required autocomplete="current-password"
                        placeholder="{{ __('Ingrese su contraseña') }}"
                        class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 ps-9 pe-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    <button type="button" @click="showPassword = !showPassword"
                        :aria-label="showPassword ? @js(__('Ocultar contraseña')) : @js(__('Mostrar contraseña'))"
                        :title="showPassword ? @js(__('Ocultar contraseña')) : @js(__('Mostrar contraseña'))"
                        class="absolute end-1.5 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-gray-400 transition-colors hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-300">
                        <svg x-show="!showPassword" class="h-4 w-4 stroke-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg x-show="showPassword" x-cloak class="h-4 w-4 stroke-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Remember / forgot -->
            <div class="flex items-center justify-between gap-3">
                <label for="remember" x-data="{ remember: false }"
                    class="flex cursor-pointer items-center text-sm font-normal text-gray-700 select-none dark:text-gray-400">
                    <span class="relative">
                        <input type="checkbox" id="remember" name="remember" class="sr-only" x-model="remember" />
                        <span :class="remember ? 'border-brand-500 bg-brand-500' : 'bg-transparent border-gray-300 dark:border-gray-700'"
                            class="me-2 flex h-4 w-4 items-center justify-center rounded border-[1.25px]">
                            <svg :class="remember ? '' : 'opacity-0'" class="h-3 w-3 stroke-white" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </span>
                    {{ __('Mantener sesión iniciada') }}
                </label>
                <a href="{{ route('password.request') }}" class="text-brand-500 hover:text-brand-600 dark:text-brand-400 text-sm font-medium">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            </div>

            <!-- Submit: disabled while sending -->
            <x-auth.button loading="sending">
                <span x-text="sending ? @js(__('Accediendo…')) : @js(__('Iniciar Sesión'))">{{ __('Iniciar Sesión') }}</span>
            </x-auth.button>
        </form>
    </x-auth.layout>
@endsection
