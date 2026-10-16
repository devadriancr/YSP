@extends('layouts.fullscreen-layout')

@section('content')
    <x-auth.layout :title="__('Restablecer contraseña')"
        :description="__('Introduce tu dirección de correo electrónico.')"
        :backUrl="route('login')"
        :backLabel="__('Regresar al inicio de sesión')">
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="space-y-5">
                <x-auth.input :label="__('Correo Electrónico')" name="email" type="email" :value="old('email')" autofocus placeholder="Ejemplo@mail.com" />
                <x-auth.button>{{ __('Continuar') }}</x-auth.button>
            </div>
        </form>
    </x-auth.layout>
@endsection
