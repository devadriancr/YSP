@extends('layouts.fullscreen-layout')

@section('content')
    <x-auth.layout :title="__('Restablecer contraseña')" :description="__('Ingresa tu nueva contraseña.')">
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div class="space-y-5">
                <x-auth.input :label="__('Correo Electrónico')" name="email" type="email" :value="old('email', $request->email)" autocomplete="username" />
                <x-auth.input :label="__('Nueva contraseña')" name="password" type="password" autocomplete="new-password" autofocus />
                <x-auth.input :label="__('Confirmar contraseña')" name="password_confirmation" type="password" autocomplete="new-password" />
                <x-auth.button>{{ __('Restablecer contraseña') }}</x-auth.button>
            </div>
        </form>
    </x-auth.layout>
@endsection
