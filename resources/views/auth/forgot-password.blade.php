@extends('layouts.fullscreen-layout')

@section('content')
    <x-auth.layout :title="__('¿Olvidaste tu contraseña?')"
        :description="__('Introduce tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.')"
        :backUrl="route('login')"
        :backLabel="__('Regresar al inicio de sesión')">

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5"
            x-data="{ sending: false }" @submit="sending = true">
            @csrf
            <x-auth.input :label="__('Correo Electrónico')" name="email" type="email" :value="old('email')" autocomplete="username" autofocus placeholder="Ejemplo@mail.com">
                <x-slot:icon>
                    <svg class="h-4 w-4 stroke-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </x-slot:icon>
            </x-auth.input>
            <x-auth.button loading="sending">
                <span x-text="sending ? @js(__('Enviando…')) : @js(__('Enviar'))">{{ __('Enviar') }}</span>
            </x-auth.button>
        </form>
    </x-auth.layout>
@endsection
